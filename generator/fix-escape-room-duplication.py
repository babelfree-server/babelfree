#!/usr/bin/env python3
"""
fix-escape-room-duplication.py
------------------------------
Fixes the double escape room problem across all 58 destination JSONs.

Problem: Each dest JSON has BOTH inline escaperoom games in games[] AND a
standalone escapeRoom key. The router serves both, so students play 2-3
escape rooms per destination.

Fix: Keep the inline escape rooms (production quality). Remove the standalone
escapeRoom key. Handle edge cases (0 or 2 inline rooms).

Also fixes dest1's arrival duplication (games[0] duplicates arrival content).
"""

import json
import os
import sys

CONTENT_DIR = "/home/babelfree.com/public_html/content"
DEST_COUNT = 58

def load_dest(n):
    path = os.path.join(CONTENT_DIR, f"dest{n}.json")
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)

def save_dest(n, data):
    path = os.path.join(CONTENT_DIR, f"dest{n}.json")
    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, indent=2, ensure_ascii=False)
        f.write("\n")

def get_inline_indices(data):
    """Return indices of games with type == 'escaperoom'."""
    return [i for i, g in enumerate(data["games"]) if g.get("type") == "escaperoom"]

def convert_standalone_to_inline(standalone):
    """Convert a standalone escapeRoom object to inline game format."""
    inline = dict(standalone)  # shallow copy

    # Ensure required inline fields
    if "label" not in inline:
        inline["label"] = "Sala de enigmas"
    if "instruction" not in inline:
        inline["instruction"] = "Resuelve los enigmas para abrir la puerta."

    # Convert fragment from bare string to object format
    if "fragment" in inline and isinstance(inline["fragment"], str):
        frag_string = inline["fragment"]
        inline["fragment"] = {
            "text": "Un fragmento brilla.",
            "questClue": "Continúa el viaje.",
            "item": frag_string
        }

    return inline

def find_cronica_index(games):
    """Find the index of the cronica game (if any)."""
    for i, g in enumerate(games):
        if g.get("type") == "cronica":
            return i
    return None

def main():
    changes = []
    errors = []

    for n in range(1, DEST_COUNT + 1):
        dest_name = f"dest{n}"
        data = load_dest(n)
        actions = []

        # --- Special case: dest1 arrival duplication ---
        if n == 1:
            g0 = data["games"][0]
            arrival = data.get("arrival", {})
            # Verify it's the duplicate narrative
            if (g0.get("type") == "narrative"
                    and len(g0.get("sections", [])) > 0
                    and len(arrival.get("sections", [])) > 0
                    and g0["sections"][0].get("body") == arrival["sections"][0].get("body")):
                data["games"].pop(0)
                actions.append("Removed games[0] (arrival duplicate narrative)")
            else:
                actions.append("WARNING: dest1 games[0] did not match arrival — skipped removal")

        # --- Count inline escape room games ---
        inline_indices = get_inline_indices(data)
        count = len(inline_indices)
        has_standalone = "escapeRoom" in data

        # --- Case 1: Exactly 1 inline escape room ---
        if count == 1:
            if has_standalone:
                del data["escapeRoom"]
                actions.append(f"Removed standalone escapeRoom (1 inline at games[{inline_indices[0]}])")
            else:
                actions.append("Already clean (1 inline, no standalone)")

        # --- Case 2: 2 inline escape rooms ---
        elif count == 2:
            first_idx = inline_indices[0]
            second_idx = inline_indices[1]
            # Remove the second inline (higher index first to avoid shift)
            removed_game = data["games"].pop(second_idx)
            actions.append(
                f"Removed second inline escaperoom at games[{second_idx}] "
                f"(room: \"{removed_game.get('room', {}).get('name', '?')}\")"
            )
            # Remove standalone
            if has_standalone:
                del data["escapeRoom"]
                actions.append("Removed standalone escapeRoom")
            actions.append(f"Kept first inline escaperoom at games[{first_idx}]")

        # --- Case 3: 0 inline escape rooms (dest4) ---
        elif count == 0:
            if has_standalone:
                standalone = data["escapeRoom"]
                inline_game = convert_standalone_to_inline(standalone)

                # Insert as second-to-last game (before cronica if present)
                cronica_idx = find_cronica_index(data["games"])
                if cronica_idx is not None:
                    insert_idx = cronica_idx
                    data["games"].insert(insert_idx, inline_game)
                    actions.append(
                        f"Converted standalone escapeRoom to inline and inserted at games[{insert_idx}] "
                        f"(before cronica at [{cronica_idx}])"
                    )
                else:
                    data["games"].append(inline_game)
                    insert_idx = len(data["games"]) - 1
                    actions.append(
                        f"Converted standalone escapeRoom to inline and appended at games[{insert_idx}] "
                        f"(no cronica found)"
                    )

                del data["escapeRoom"]
                actions.append("Removed standalone escapeRoom after conversion")
            else:
                actions.append("ERROR: No inline and no standalone escape room!")
                errors.append(dest_name)

        # --- Case 4: More than 2 inline (unexpected) ---
        else:
            # Keep only the first, remove all others (highest index first)
            for idx in reversed(inline_indices[1:]):
                removed = data["games"].pop(idx)
                actions.append(
                    f"Removed extra inline escaperoom at games[{idx}] "
                    f"(room: \"{removed.get('room', {}).get('name', '?')}\")"
                )
            if has_standalone:
                del data["escapeRoom"]
                actions.append("Removed standalone escapeRoom")
            actions.append(f"Kept first inline escaperoom at games[{inline_indices[0]}]")

        # --- Save ---
        save_dest(n, data)

        # --- Verification ---
        verify_data = load_dest(n)
        verify_inline = get_inline_indices(verify_data)
        verify_standalone = "escapeRoom" in verify_data
        ok = len(verify_inline) == 1 and not verify_standalone
        status = "OK" if ok else "FAIL"
        if not ok:
            errors.append(dest_name)

        changes.append({
            "dest": dest_name,
            "actions": actions,
            "status": status,
            "final_inline_count": len(verify_inline),
            "final_has_standalone": verify_standalone,
            "final_total_games": len(verify_data["games"])
        })

    # --- Print summary ---
    print("=" * 70)
    print("ESCAPE ROOM DEDUPLICATION SUMMARY")
    print("=" * 70)
    print()

    for c in changes:
        print(f"  {c['dest']:8s} [{c['status']}]  games={c['final_total_games']:2d}  ", end="")
        print(f"inline={c['final_inline_count']}  standalone={'YES' if c['final_has_standalone'] else 'no'}")
        for a in c["actions"]:
            print(f"           -> {a}")
        print()

    print("=" * 70)
    total_ok = sum(1 for c in changes if c["status"] == "OK")
    print(f"  {total_ok}/{len(changes)} destinations verified OK")
    if errors:
        print(f"  ERRORS in: {', '.join(errors)}")
    else:
        print("  No errors.")
    print("=" * 70)

    return 0 if not errors else 1

if __name__ == "__main__":
    sys.exit(main())
