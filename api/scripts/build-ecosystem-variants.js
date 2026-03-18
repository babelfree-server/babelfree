#!/usr/bin/env node
/**
 * Build Ecosystem Variants — Expands templates into ecosystem-specific game instances.
 *
 * Usage:
 *   node build-ecosystem-variants.js --dry-run           # Show stats only
 *   node build-ecosystem-variants.js --apply             # Write expanded files
 *   node build-ecosystem-variants.js --apply --dest=1-12 # Only A1
 *   node build-ecosystem-variants.js --apply --dest=1    # Single dest
 */

const fs = require('fs');
const path = require('path');

const TemplateGenerator = require(path.join(__dirname, '../../js/template-generator.js'));

// Parse args
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
const outputDir = path.join(contentDir, 'expanded');

if (!dryRun && !fs.existsSync(outputDir)) {
  fs.mkdirSync(outputDir, { recursive: true });
}

console.log(`\n${'='.repeat(60)}`);
console.log(`Ecosystem Variant Builder — dest${destStart}-${destEnd}`);
console.log(`Mode: ${dryRun ? 'DRY RUN' : 'APPLY'}`);
console.log(`${'='.repeat(60)}\n`);

let totalCore = 0, totalExpanded = 0, totalEcoVariants = 0;

for (let d = destStart; d <= destEnd; d++) {
  const tmplFile = path.join(templateDir, `dest${d}-templates.json`);
  if (!fs.existsSync(tmplFile)) {
    console.log(`  dest${d}: SKIP (no template file)`);
    continue;
  }

  const tmplData = JSON.parse(fs.readFileSync(tmplFile, 'utf8'));
  const templateCount = (tmplData.templates || []).length;

  // Count templates with ecosystem slots
  const ecoTemplates = (tmplData.templates || []).filter(t =>
    t._template && t._template.ecosystemSlots && t._template.ecosystemSlots.length > 0
  ).length;

  // Run expansion
  const expanded = TemplateGenerator.expand(tmplData, {
    includeEcoVariants: true,
    includeInputModes: false,  // Not for static build
    includeSpiralGames: false
  });

  const stats = expanded._stats || {};
  const coreGames = templateCount;
  const ecoVariants = stats.ecosystemVariants || 0;
  const totalGames = stats.totalGames || expanded.games.length;

  totalCore += coreGames;
  totalExpanded += totalGames;
  totalEcoVariants += ecoVariants;

  console.log(`  dest${String(d).padStart(2, '0')}: ${coreGames} core → ${totalGames} total (${ecoVariants} eco variants from ${ecoTemplates} slotted templates)`);

  if (!dryRun) {
    // Write expanded file
    const outFile = path.join(outputDir, `dest${d}-expanded.json`);

    // Also merge with existing dest file to preserve arrival/departure/characterMeta/etc
    const origFile = path.join(contentDir, `dest${d}.json`);
    let origData = {};
    if (fs.existsSync(origFile)) {
      origData = JSON.parse(fs.readFileSync(origFile, 'utf8'));
    }

    // The expanded data contains the template-derived games.
    // We keep the original dest's non-game content and append ecosystem variants.
    const merged = {
      meta: origData.meta || expanded.meta,
      preArrival: origData.preArrival || expanded.preArrival || [],
      arrival: origData.arrival || expanded.arrival || {},
      games: origData.games || [], // keep original hand-crafted games
      ecosystemGames: expanded.games.filter(g =>
        g._template && g._template.isVariant
      ),
      departure: origData.departure || expanded.departure || {},
      characterMeta: origData.characterMeta || expanded.characterMeta || {},
      characterLines: origData.characterLines || {},
      _expansionStats: {
        coreTemplates: coreGames,
        ecosystemVariants: ecoVariants,
        totalExpandedGames: totalGames,
        generatedAt: new Date().toISOString()
      }
    };

    // Also write the full expanded template output separately
    fs.writeFileSync(outFile, JSON.stringify(expanded, null, 2), 'utf8');

    // Update the main dest file with the ecosystem games section
    const mainOutFile = path.join(contentDir, `dest${d}.json`);
    fs.writeFileSync(mainOutFile, JSON.stringify(merged, null, 2), 'utf8');

    console.log(`         → Wrote ${outFile}`);
    console.log(`         → Updated ${mainOutFile}`);
  }
}

console.log(`\n${'='.repeat(60)}`);
console.log(`TOTALS`);
console.log(`  Core templates:     ${totalCore}`);
console.log(`  Ecosystem variants: ${totalEcoVariants}`);
console.log(`  Total games:        ${totalExpanded}`);
console.log(`  Expansion ratio:    ${(totalExpanded / totalCore).toFixed(1)}×`);
console.log(`${'='.repeat(60)}\n`);

if (dryRun) {
  console.log('This was a DRY RUN. Run with --apply to write files.\n');
}
