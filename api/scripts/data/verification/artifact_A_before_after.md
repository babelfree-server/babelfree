# Artifact A: Before/After Comparison (5 verbs)

Source: generator output (Spanish keys) → Processed: post-processor output (spec-aligned)

## hablar
**verb_type**: `regular -ar`

### imperative.affirmative
BEFORE (source imperativo.afirmativo):
```json
{
    "tú": "habla",
    "vos": "hablá",
    "él": "hable",
    "nosotros": "hablemos",
    "ustedes": "hablen",
    "ellos": "hablen"
}
```
AFTER (spec-normalized):
```json
{
    "tú": "habla",
    "usted": "hable",
    "nosotros": "hablemos",
    "ustedes": "hablen",
    "yo": null,
    "él/ella/usted": null,
    "vosotros": null,
    "ellos/ellas/ustedes": null
}
```

### imperative.negative (NEW — derived from subjunctive.present)
SOURCE (subjuntivo.presente):
```json
{
    "yo": "hable",
    "tú": "hables",
    "vos": "hables",
    "él": "hable",
    "nosotros": "hablemos",
    "ustedes": "hablen",
    "ellos": "hablen"
}
```
DERIVED (via subj_key_for_person mapping):
```json
{
    "tú": "no hables",
    "usted": "no hable",
    "nosotros": "no hablemos",
    "vosotros": null,
    "ustedes": "no hablen"
}
```

### indicative.present (person key normalization + vos removal)
BEFORE:
```json
{
    "yo": "hablo",
    "tú": "hablas",
    "vos": "hablás",
    "él": "habla",
    "nosotros": "hablamos",
    "ustedes": "hablan",
    "ellos": "hablan"
}
```
AFTER:
```json
{
    "yo": "hablo",
    "tú": "hablas",
    "él/ella/usted": "habla",
    "nosotros": "hablamos",
    "ellos/ellas/ustedes": "hablan",
    "vosotros": null
}
```

### postprocess_meta
```json
{
    "negative_imperative_derived": true,
    "derived_from": "subjunctive.present",
    "timestamp": "2026-02-23T15:24:20.873370Z"
}
```

---

## ser
**verb_type**: `irregular -er`

### imperative.affirmative
BEFORE (source imperativo.afirmativo):
```json
{
    "tú": "sé",
    "vos": "sé",
    "él": "sea",
    "nosotros": "seamos",
    "ustedes": "sean",
    "ellos": "sean"
}
```
AFTER (spec-normalized):
```json
{
    "tú": "sé",
    "usted": "sea",
    "nosotros": "seamos",
    "ustedes": "sean",
    "yo": null,
    "él/ella/usted": null,
    "vosotros": null,
    "ellos/ellas/ustedes": null
}
```

### imperative.negative (NEW — derived from subjunctive.present)
SOURCE (subjuntivo.presente):
```json
{
    "yo": "sea",
    "tú": "seas",
    "vos": "seas",
    "él": "sea",
    "nosotros": "seamos",
    "ustedes": "sean",
    "ellos": "sean"
}
```
DERIVED (via subj_key_for_person mapping):
```json
{
    "tú": "no seas",
    "usted": "no sea",
    "nosotros": "no seamos",
    "vosotros": null,
    "ustedes": "no sean"
}
```

### indicative.present (person key normalization + vos removal)
BEFORE:
```json
{
    "yo": "soy",
    "tú": "eres",
    "vos": "sos",
    "él": "es",
    "nosotros": "somos",
    "ustedes": "son",
    "ellos": "son"
}
```
AFTER:
```json
{
    "yo": "soy",
    "tú": "eres",
    "él/ella/usted": "es",
    "nosotros": "somos",
    "ellos/ellas/ustedes": "son",
    "vosotros": null
}
```

### postprocess_meta
```json
{
    "negative_imperative_derived": true,
    "derived_from": "subjunctive.present",
    "timestamp": "2026-02-23T15:24:20.866906Z"
}
```

---

## ir
**verb_type**: `irregular -ir`

### imperative.affirmative
BEFORE (source imperativo.afirmativo):
```json
{
    "tú": "ve",
    "vos": "andá",
    "él": "vaya",
    "nosotros": "vamos",
    "ustedes": "vayan",
    "ellos": "vayan"
}
```
AFTER (spec-normalized):
```json
{
    "tú": "ve",
    "usted": "vaya",
    "nosotros": "vamos",
    "ustedes": "vayan",
    "yo": null,
    "él/ella/usted": null,
    "vosotros": null,
    "ellos/ellas/ustedes": null
}
```

### imperative.negative (NEW — derived from subjunctive.present)
SOURCE (subjuntivo.presente):
```json
{
    "yo": "vaya",
    "tú": "vayas",
    "vos": "vayas",
    "él": "vaya",
    "nosotros": "vayamos",
    "ustedes": "vayan",
    "ellos": "vayan"
}
```
DERIVED (via subj_key_for_person mapping):
```json
{
    "tú": "no vayas",
    "usted": "no vaya",
    "nosotros": "no vayamos",
    "vosotros": null,
    "ustedes": "no vayan"
}
```

### indicative.present (person key normalization + vos removal)
BEFORE:
```json
{
    "yo": "voy",
    "tú": "vas",
    "vos": "vas",
    "él": "va",
    "nosotros": "vamos",
    "ustedes": "van",
    "ellos": "van"
}
```
AFTER:
```json
{
    "yo": "voy",
    "tú": "vas",
    "él/ella/usted": "va",
    "nosotros": "vamos",
    "ellos/ellas/ustedes": "van",
    "vosotros": null
}
```

### postprocess_meta
```json
{
    "negative_imperative_derived": true,
    "derived_from": "subjunctive.present",
    "timestamp": "2026-02-23T15:24:20.873215Z"
}
```

---

## dormir
**verb_type**: `irregular -ir`

### imperative.affirmative
BEFORE (source imperativo.afirmativo):
```json
{
    "tú": "duerme",
    "vos": "dormí",
    "él": "duerma",
    "nosotros": "durmamos",
    "ustedes": "duerman",
    "ellos": "duerman"
}
```
AFTER (spec-normalized):
```json
{
    "tú": "duerme",
    "usted": "duerma",
    "nosotros": "durmamos",
    "ustedes": "duerman",
    "yo": null,
    "él/ella/usted": null,
    "vosotros": null,
    "ellos/ellas/ustedes": null
}
```

### imperative.negative (NEW — derived from subjunctive.present)
SOURCE (subjuntivo.presente):
```json
{
    "yo": "duerma",
    "tú": "duermas",
    "vos": "duermas",
    "él": "duerma",
    "nosotros": "durmamos",
    "ustedes": "duerman",
    "ellos": "duerman"
}
```
DERIVED (via subj_key_for_person mapping):
```json
{
    "tú": "no duermas",
    "usted": "no duerma",
    "nosotros": "no durmamos",
    "vosotros": null,
    "ustedes": "no duerman"
}
```

### indicative.present (person key normalization + vos removal)
BEFORE:
```json
{
    "yo": "duermo",
    "tú": "duermes",
    "vos": "dormís",
    "él": "duerme",
    "nosotros": "dormimos",
    "ustedes": "duermen",
    "ellos": "duermen"
}
```
AFTER:
```json
{
    "yo": "duermo",
    "tú": "duermes",
    "él/ella/usted": "duerme",
    "nosotros": "dormimos",
    "ellos/ellas/ustedes": "duermen",
    "vosotros": null
}
```

### postprocess_meta
```json
{
    "negative_imperative_derived": true,
    "derived_from": "subjunctive.present",
    "timestamp": "2026-02-23T15:24:20.874019Z"
}
```

---

## pedir
**verb_type**: `irregular -ir`

### imperative.affirmative
BEFORE (source imperativo.afirmativo):
```json
{
    "tú": "pide",
    "vos": "pedí",
    "él": "pida",
    "nosotros": "pidamos",
    "ustedes": "pidan",
    "ellos": "pidan"
}
```
AFTER (spec-normalized):
```json
{
    "tú": "pide",
    "usted": "pida",
    "nosotros": "pidamos",
    "ustedes": "pidan",
    "yo": null,
    "él/ella/usted": null,
    "vosotros": null,
    "ellos/ellas/ustedes": null
}
```

### imperative.negative (NEW — derived from subjunctive.present)
SOURCE (subjuntivo.presente):
```json
{
    "yo": "pida",
    "tú": "pidas",
    "vos": "pidas",
    "él": "pida",
    "nosotros": "pidamos",
    "ustedes": "pidan",
    "ellos": "pidan"
}
```
DERIVED (via subj_key_for_person mapping):
```json
{
    "tú": "no pidas",
    "usted": "no pida",
    "nosotros": "no pidamos",
    "vosotros": null,
    "ustedes": "no pidan"
}
```

### indicative.present (person key normalization + vos removal)
BEFORE:
```json
{
    "yo": "pido",
    "tú": "pides",
    "vos": "pedís",
    "él": "pide",
    "nosotros": "pedimos",
    "ustedes": "piden",
    "ellos": "piden"
}
```
AFTER:
```json
{
    "yo": "pido",
    "tú": "pides",
    "él/ella/usted": "pide",
    "nosotros": "pedimos",
    "ellos/ellas/ustedes": "piden",
    "vosotros": null
}
```

### postprocess_meta
```json
{
    "negative_imperative_derived": true,
    "derived_from": "subjunctive.present",
    "timestamp": "2026-02-23T15:24:20.952429Z"
}
```

---

