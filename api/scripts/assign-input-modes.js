#!/usr/bin/env node
/**
 * Assign Input Modes — adds appropriate inputModes to templates based on game type.
 *
 * Game types that support multiple input modes:
 *   fill, conjugation, guardian, translation, listening → choice, drag, typing, voice
 *   pair, category, match → choice, drag
 *   pick → choice, typing
 *   order → choice, drag
 *
 * Game types that are fixed (single mode):
 *   skit, escaperoom, cronica, portafolio, autoevaluacion, cultura,
 *   explorador, narrative, ritmo, cartografo, conversation, story,
 *   conjuro, sombra, pregonero, tertulia
 *
 * Usage:
 *   node assign-input-modes.js --dry-run
 *   node assign-input-modes.js --apply
 */

const fs = require('fs');
const path = require('path');

const args = process.argv.slice(2);
const dryRun = !args.includes('--apply');

const templateDir = path.join(__dirname, '../../content/templates');

// Game type → supported input modes
const MODE_MAP = {
  fill:        ['choice', 'drag', 'typing', 'voice'],
  conjugation: ['choice', 'drag', 'typing', 'voice'],
  guardian:    ['choice', 'typing', 'voice'],
  translation: ['choice', 'typing', 'voice'],
  listening:   ['choice', 'typing'],
  pick:        ['choice', 'typing'],
  pair:        ['choice', 'drag'],
  category:    ['choice', 'drag'],
  match:       ['choice', 'drag'],
  order:       ['choice', 'drag'],
  corrector:   ['choice', 'typing'],
};

let totalUpdated = 0, totalSkipped = 0, filesModified = 0;

for (let d = 1; d <= 58; d++) {
  const file = path.join(templateDir, `dest${d}-templates.json`);
  if (!fs.existsSync(file)) continue;

  const data = JSON.parse(fs.readFileSync(file, 'utf8'));
  let modified = false;

  for (const tmpl of (data.templates || [])) {
    const type = tmpl.type;
    const meta = tmpl._template || {};
    const currentModes = meta.inputModes || ['choice'];
    const targetModes = MODE_MAP[type];

    if (!targetModes) {
      totalSkipped++;
      continue;
    }

    // Only update if we'd add new modes
    const newModes = targetModes.filter(m => !currentModes.includes(m));
    if (newModes.length === 0) {
      totalSkipped++;
      continue;
    }

    if (!tmpl._template) tmpl._template = {};
    tmpl._template.inputModes = targetModes;
    totalUpdated++;
    modified = true;
  }

  if (modified) {
    filesModified++;
    if (!dryRun) {
      fs.writeFileSync(file, JSON.stringify(data, null, 2), 'utf8');
    }
  }
}

console.log(`\nInput Mode Assignment — ${dryRun ? 'DRY RUN' : 'APPLIED'}`);
console.log(`  Templates updated: ${totalUpdated}`);
console.log(`  Templates skipped: ${totalSkipped} (fixed type or already complete)`);
console.log(`  Files modified:    ${filesModified}`);

if (dryRun) console.log('\nRun with --apply to write changes.\n');
