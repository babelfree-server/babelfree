#!/usr/bin/env node
/**
 * Build Input Mode Variants — Expands templates into difficulty-progressive game instances.
 *
 * For each template with multiple inputModes, generates:
 *   - choice (default, easiest — pick from options)
 *   - drag (intermediate — drag to place)
 *   - typing (harder — type the answer)
 *   - voice (hardest — speak the answer)
 *
 * Variants are stored in dest{N}.json under `inputModeGames`.
 * The engine serves progressively harder modes as the student masters vocabulary.
 *
 * Usage:
 *   node build-input-modes.js --dry-run
 *   node build-input-modes.js --apply
 *   node build-input-modes.js --apply --dest=1-12
 */

const fs = require('fs');
const path = require('path');

const TemplateGenerator = require(path.join(__dirname, '../../js/template-generator.js'));

const args = process.argv.slice(2);
const dryRun = !args.includes('--apply');
let destStart = 1, destEnd = 58;

const destArg = args.find(a => a.startsWith('--dest='));
if (destArg) {
  const val = destArg.split('=')[1];
  if (val.includes('-')) {
    const [s, e] = val.split('-').map(Number);
    destStart = s; destEnd = e;
  } else {
    destStart = destEnd = Number(val);
  }
}

const contentDir = path.join(__dirname, '../../content');
const templateDir = path.join(contentDir, 'templates');

console.log(`\n${'='.repeat(60)}`);
console.log(`Input Mode Variant Builder — dest${destStart}-${destEnd}`);
console.log(`Mode: ${dryRun ? 'DRY RUN' : 'APPLY'}`);
console.log(`${'='.repeat(60)}\n`);

let totalCore = 0, totalModeVariants = 0;

for (let d = destStart; d <= destEnd; d++) {
  const tmplFile = path.join(templateDir, `dest${d}-templates.json`);
  if (!fs.existsSync(tmplFile)) {
    console.log(`  dest${d}: SKIP`);
    continue;
  }

  const tmplData = JSON.parse(fs.readFileSync(tmplFile, 'utf8'));
  const templates = tmplData.templates || [];
  totalCore += templates.length;

  // Run expansion with input modes enabled
  const expanded = TemplateGenerator.expand(tmplData, {
    includeEcoVariants: false,
    includeInputModes: true
  });

  // Extract only the input mode variants (not the core games)
  const modeVariants = expanded.games.filter(g =>
    g._template && g._template.inputMode && g._template.inputMode !== 'choice'
  );

  totalModeVariants += modeVariants.length;

  // Count by mode
  const byCounts = {};
  for (const v of modeVariants) {
    const m = v._template.inputMode;
    byCounts[m] = (byCounts[m] || 0) + 1;
  }
  const modeStr = Object.entries(byCounts).map(([k, v]) => `${k}:${v}`).join(' ');

  console.log(`  dest${String(d).padStart(2, '0')}: ${templates.length} core → +${modeVariants.length} mode variants (${modeStr})`);

  if (!dryRun && modeVariants.length > 0) {
    // Read the current dest file and add inputModeGames
    const destFile = path.join(contentDir, `dest${d}.json`);
    const destData = JSON.parse(fs.readFileSync(destFile, 'utf8'));

    destData.inputModeGames = modeVariants;

    // Update expansion stats
    if (!destData._expansionStats) destData._expansionStats = {};
    destData._expansionStats.inputModeVariants = modeVariants.length;
    destData._expansionStats.inputModesGeneratedAt = new Date().toISOString();

    fs.writeFileSync(destFile, JSON.stringify(destData, null, 2), 'utf8');
  }
}

console.log(`\n${'='.repeat(60)}`);
console.log(`TOTALS`);
console.log(`  Core templates:       ${totalCore}`);
console.log(`  Input mode variants:  ${totalModeVariants}`);
console.log(`  Avg per destination:  ${(totalModeVariants / (destEnd - destStart + 1)).toFixed(1)}`);
console.log(`${'='.repeat(60)}\n`);

if (dryRun) console.log('This was a DRY RUN. Run with --apply to write files.\n');
