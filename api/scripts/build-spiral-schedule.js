#!/usr/bin/env node
/**
 * Build Spiral Schedule — Places games into future destinations for Fibonacci re-encounter.
 *
 * Steiner's core pedagogical principle: encounter → sleep → re-encounter at deeper level.
 * Each template with spiralReturn: [destN, destM] generates difficulty-adjusted copies
 * that are placed into those target destinations.
 *
 * Pass 1 (first spiral): extra distractors, reduced time limits
 * Pass 2 (second spiral): hints/tips removed entirely
 *
 * Stored in dest{N}.json under `spiralGames`.
 *
 * Usage:
 *   node build-spiral-schedule.js --dry-run
 *   node build-spiral-schedule.js --apply
 */

const fs = require('fs');
const path = require('path');

const TemplateGenerator = require(path.join(__dirname, '../../js/template-generator.js'));

const args = process.argv.slice(2);
const dryRun = !args.includes('--apply');

const contentDir = path.join(__dirname, '../../content');
const templateDir = path.join(contentDir, 'templates');

console.log(`\n${'='.repeat(60)}`);
console.log(`Spiral Schedule Builder — Steiner re-encounter rhythm`);
console.log(`Mode: ${dryRun ? 'DRY RUN' : 'APPLY'}`);
console.log(`${'='.repeat(60)}\n`);

// Pass 1: Expand all templates and collect spiral queues
const spiralBuckets = {}; // destNumber → [{ game, fromDest, spiralPass }]
let totalSpiralGames = 0;

for (let d = 1; d <= 58; d++) {
  const tmplFile = path.join(templateDir, `dest${d}-templates.json`);
  if (!fs.existsSync(tmplFile)) continue;

  const tmplData = JSON.parse(fs.readFileSync(tmplFile, 'utf8'));

  // Use the generator's expansion with spiral collection
  const expanded = TemplateGenerator.expand(tmplData, {
    includeEcoVariants: false,
    includeInputModes: false
  });

  // The spiral queue contains { destNumber, game } entries
  const queue = expanded._spiralQueue || [];

  for (const entry of queue) {
    const targetDest = entry.destNumber;
    if (targetDest < 1 || targetDest > 58) continue;

    if (!spiralBuckets[targetDest]) spiralBuckets[targetDest] = [];
    spiralBuckets[targetDest].push(entry.game);
    totalSpiralGames++;
  }
}

// Pass 2: Write spiral games into target destinations
let destsWithSpirals = 0;

for (let d = 1; d <= 58; d++) {
  const games = spiralBuckets[d] || [];
  if (games.length === 0) {
    console.log(`  dest${String(d).padStart(2, '0')}: 0 spiral games`);
    continue;
  }

  destsWithSpirals++;

  // Count by source destination
  const sources = {};
  for (const g of games) {
    const from = g._template?.spiralFrom || '?';
    sources[from] = (sources[from] || 0) + 1;
  }
  const sourceStr = Object.entries(sources)
    .sort((a, b) => a[0] - b[0])
    .map(([k, v]) => `from dest${k}:${v}`)
    .join(' ');

  console.log(`  dest${String(d).padStart(2, '0')}: ${games.length} spiral games (${sourceStr})`);

  if (!dryRun) {
    const destFile = path.join(contentDir, `dest${d}.json`);
    const destData = JSON.parse(fs.readFileSync(destFile, 'utf8'));

    destData.spiralGames = games;

    if (!destData._expansionStats) destData._expansionStats = {};
    destData._expansionStats.spiralGames = games.length;
    destData._expansionStats.spiralGeneratedAt = new Date().toISOString();

    fs.writeFileSync(destFile, JSON.stringify(destData, null, 2), 'utf8');
  }
}

console.log(`\n${'='.repeat(60)}`);
console.log(`TOTALS`);
console.log(`  Total spiral placements: ${totalSpiralGames}`);
console.log(`  Destinations receiving:  ${destsWithSpirals}`);
console.log(`  Avg per destination:     ${(totalSpiralGames / Math.max(destsWithSpirals, 1)).toFixed(1)}`);
console.log(`${'='.repeat(60)}\n`);

if (dryRun) console.log('This was a DRY RUN. Run with --apply to write files.\n');
