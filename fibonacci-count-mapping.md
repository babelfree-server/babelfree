# FIBONACCI COUNT MAPPING — GAME & CONTENT DISTRIBUTION
# PROJECT: LA AVENTURA DE YAGUARÁ
# PURPOSE: Map Fibonacci growth to concrete game counts and learning depth
# GOAL: Reach ~500+ activities without linear inflation or content bloat

#####################################################################
# 1. DESIGN CONSTRAINTS
#####################################################################

We need a counting system that:

- Feels organic, not industrial
- Prioritizes repetition early and depth later
- Supports long-term retention
- Allows reuse of mechanics
- Scales to hundreds of activities
- Aligns with CEFR (A1–C2)

We reject:
- Flat grids (levels × modules × games)
- Equal-sized units
- Constant content introduction

#####################################################################
# 2. FIBONACCI AS CONTENT LAW
#####################################################################

Base Fibonacci sequence (starting small on purpose):

F = [1, 1, 2, 3, 5, 8, 13, 21, 34, 55]

Interpretation:
- Each number = number of GAME INSTANCES in a spiral
- A spiral = one CEFR-aligned depth cycle
- Game instances reuse existing mechanics

We will use the **last 6 meaningful Fibonacci values** for A1–C2.

#####################################################################
# 3. SPIRAL-TO-CEFR MAPPING
#####################################################################

SPIRAL 1 — A1
Fibonacci count: 5
Purpose: Language awakening

Games:
- 5 very small games
- Heavy repetition
- Extremely short play loops

Archetypes used:
- Naming Ritual
- Guided Movement

--------------------------------------------

SPIRAL 2 — A2
Fibonacci count: 8
Purpose: Sentence emergence

Games:
- 8 games
- Same mechanics as A1
- Slightly longer loops

Archetypes used:
- Naming Ritual (expanded)
- Guided Movement (expanded)
- Story Weaving (very simple)

--------------------------------------------

SPIRAL 3 — B1
Fibonacci count: 13
Purpose: Memory & narration

Games:
- 13 games
- Return to A1/A2 content with time dimension

Archetypes used:
- Naming Ritual (past)
- Guided Movement (narrated)
- Story Weaving (full)

--------------------------------------------

SPIRAL 4 — B2
Fibonacci count: 21
Purpose: Nuance & comparison

Games:
- 21 games
- Same places, different reactions

Archetypes used:
- All previous
- Dialogue with Living World (introduced)

--------------------------------------------

SPIRAL 5 — C1
Fibonacci count: 34
Purpose: Intention & abstraction

Games:
- 34 games
- Fewer new words, more interpretation

Archetypes used:
- All previous
- Symbol Interpretation (introduced)

--------------------------------------------

SPIRAL 6 — C2
Fibonacci count: 55
Purpose: Responsibility & authorship

Games:
- 55 games
- Learner creates language outcomes

Archetypes used:
- All previous
- Restoring Balance (introduced)

#####################################################################
# 4. TOTAL CORE GAME COUNT
#####################################################################

TOTAL_GAMES_PER_ECOSYSTEM =
5 + 8 + 13 + 21 + 34 + 55
= 136 game instances

IMPORTANT:
These are NOT unique mechanics.
They are EXPERIENCES built from reused templates.

#####################################################################
# 5. HOW WE REACH ~528 ACTIVITIES
#####################################################################

We multiply spirals across ECOSYSTEMS / STORY DOMAINS.

Example ecosystems:
- River
- Forest
- Mountain
- Coast

If we define:

NUMBER_OF_ECOSYSTEMS = 4

Then:

136 games × 4 ecosystems = 544 activities

This matches the original ~528 goal,
but with:
- Fewer mechanics
- Higher coherence
- Better retention

#####################################################################
# 6. WHY THIS IS PEDAGOGICALLY SOUND
#####################################################################

Early stages:
- Small Fibonacci numbers
- Maximum repetition
- Minimal cognitive load

Later stages:
- Large Fibonacci numbers
- Same mechanics, deeper meaning
- Increased ethical and social complexity

Learner feels:
- Familiarity
- Growth
- Return

Not:
- Overload
- Grind
- Artificial progression

#####################################################################
# 7. CONTENT REUSE MATRIX
#####################################################################

A single GAME TEMPLATE may appear:

- In Spiral 1 (basic noun)
- In Spiral 3 (past tense)
- In Spiral 5 (metaphor)
- In Spiral 6 (ethical implication)

Thus:
GAME_TEMPLATE_COUNT ≈ 30–40
GAME_INSTANCE_COUNT ≈ 500+

#####################################################################
# 8. IMPLEMENTATION NOTE (FOR ENGINE DESIGN)
#####################################################################

Engine should support:
- Archetype-based instantiation
- Difficulty via parameters, not new code
- Content injection via data (JSON / CMS)

Each game instance defined by:
- Archetype
- Spiral (depth)
- Ecosystem
- Language parameters
- Consequence rules

#####################################################################
# 9. FINAL CONCLUSION
#####################################################################

Fibonacci is used to control:
- WHEN new things appear
- HOW OFTEN things return
- HOW DEEP meaning becomes

It replaces:
- Flat level counts
- Arbitrary numbers
- Content inflation

With:
- Living growth
- Spiral learning
- Cultural coherence

END OF FIBONACCI COUNT MAPPING.
