# Artifact C: Post-Processor Validation Report

**Date:** 2026-02-23 15:25:24 UTC
**Source:** conjugations_es.json (562 verbs)
**Processed:** conjugations_es_processed.json (562 verbs)

## Output spec compliance

| Field | Spec | Status |
|-------|------|--------|
| `lemma` | string | PASS |
| `verb_type` | string | PASS |
| `postprocess_meta` | object | PASS |
| `conjugations.indicative` | object | PASS |
| `conjugations.subjunctive` | object | PASS |
| `conjugations.imperative.affirmative` | object | PASS |
| `conjugations.imperative.negative` | object | PASS |
| PERSONS_FULL in indicative | 6 keys | PASS |
| IMPERATIVE_PERSONS in negative | 5 keys | PASS |
| Mood keys | English | PASS |
| Tense keys | English | PASS |

## Per-verb validation

| # | Verb | verb_type | neg. persons | no vos | forms traceable | meta | Status |
|---|------|-----------|:---:|:---:|:---:|:---:|:---:|
| 1 | ser | irregular -er | yes | yes | yes | yes | PASS |
| 2 | estar | irregular -ar | yes | yes | yes | yes | PASS |
| 3 | tener | irregular -er | yes | yes | yes | yes | PASS |
| 4 | ir | irregular -ir | yes | yes | yes | yes | PASS |
| 5 | hacer | irregular -er | yes | yes | yes | yes | PASS |
| 6 | hablar | regular -ar | yes | yes | yes | yes | PASS |
| 7 | comer | regular -er | yes | yes | yes | yes | PASS |
| 8 | vivir | regular -ir | yes | yes | yes | yes | PASS |
| 9 | poder | irregular -er | yes | yes | yes | yes | PASS |
| 10 | querer | irregular -er | yes | yes | yes | yes | PASS |
| 11 | saber | irregular -er | yes | yes | yes | yes | PASS |
| 12 | dar | irregular -ar | yes | yes | yes | yes | PASS |
| 13 | decir | irregular -ir | yes | yes | yes | yes | PASS |
| 14 | venir | irregular -ir | yes | yes | yes | yes | PASS |
| 15 | dormir | irregular -ir | yes | yes | yes | yes | PASS |
| 16 | ver | irregular -er | yes | yes | yes | yes | PASS |
| 17 | pasar | regular -ar | yes | yes | yes | yes | PASS |
| 18 | llevar | regular -ar | yes | yes | yes | yes | PASS |
| 19 | dejar | regular -ar | yes | yes | yes | yes | PASS |
| 20 | llamar | regular -ar | yes | yes | yes | yes | PASS |
| 21 | tratar | regular -ar | yes | yes | yes | yes | PASS |
| 22 | mirar | regular -ar | yes | yes | yes | yes | PASS |
| 23 | esperar | regular -ar | yes | yes | yes | yes | PASS |
| 24 | entrar | regular -ar | yes | yes | yes | yes | PASS |
| 25 | trabajar | regular -ar | yes | yes | yes | yes | PASS |
| 26 | estudiar | regular -ar | yes | yes | yes | yes | PASS |
| 27 | comprar | regular -ar | yes | yes | yes | yes | PASS |
| 28 | usar | regular -ar | yes | yes | yes | yes | PASS |
| 29 | bajar | regular -ar | yes | yes | yes | yes | PASS |
| 30 | ganar | regular -ar | yes | yes | yes | yes | PASS |
| 31 | olvidar | regular -ar | yes | yes | yes | yes | PASS |
| 32 | cambiar | regular -ar | yes | yes | yes | yes | PASS |
| 33 | escuchar | regular -ar | yes | yes | yes | yes | PASS |
| 34 | terminar | regular -ar | yes | yes | yes | yes | PASS |
| 35 | crear | regular -ar | yes | yes | yes | yes | PASS |
| 36 | aceptar | regular -ar | yes | yes | yes | yes | PASS |
| 37 | evitar | regular -ar | yes | yes | yes | yes | PASS |
| 38 | llegar | regular -ar | yes | yes | yes | yes | PASS |
| 39 | buscar | regular -ar | yes | yes | yes | yes | PASS |
| 40 | pagar | regular -ar | yes | yes | yes | yes | PASS |
| 41 | explicar | regular -ar | yes | yes | yes | yes | PASS |
| 42 | pensar | irregular -ar | yes | yes | yes | yes | PASS |
| 43 | cerrar | irregular -ar | yes | yes | yes | yes | PASS |
| 44 | empezar | irregular -ar | yes | yes | yes | yes | PASS |
| 45 | encontrar | irregular -ar | yes | yes | yes | yes | PASS |
| 46 | contar | irregular -ar | yes | yes | yes | yes | PASS |
| 47 | recordar | irregular -ar | yes | yes | yes | yes | PASS |
| 48 | mostrar | irregular -ar | yes | yes | yes | yes | PASS |
| 49 | aprender | regular -er | yes | yes | yes | yes | PASS |
| 50 | vender | regular -er | yes | yes | yes | yes | PASS |
| 51 | creer | regular -er | yes | yes | yes | yes | PASS |
| 52 | leer | regular -er | yes | yes | yes | yes | PASS |
| 53 | perder | irregular -er | yes | yes | yes | yes | PASS |
| 54 | existir | regular -ir | yes | yes | yes | yes | PASS |
| 55 | escribir | regular -ir | yes | yes | yes | yes | PASS |
| 56 | subir | regular -ir | yes | yes | yes | yes | PASS |
| 57 | permitir | regular -ir | yes | yes | yes | yes | PASS |
| 58 | decidir | regular -ir | yes | yes | yes | yes | PASS |
| 59 | abrir | regular -ir | yes | yes | yes | yes | PASS |
| 60 | seguir | irregular -ir | yes | yes | yes | yes | PASS |
| 61 | sentir | irregular -ir | yes | yes | yes | yes | PASS |
| 62 | caer | irregular -er | yes | yes | yes | yes | PASS |
| 63 | ofrecer | irregular -er | yes | yes | yes | yes | PASS |
| 64 | haber | irregular -er | yes | yes | yes | yes | PASS |
| 65 | deber | regular -er | yes | yes | yes | yes | PASS |
| 66 | poner | irregular -er | yes | yes | yes | yes | PASS |
| 67 | parecer | irregular -er | yes | yes | yes | yes | PASS |
| 68 | imaginar | regular -ar | yes | yes | yes | yes | PASS |
| 69 | considerar | regular -ar | yes | yes | yes | yes | PASS |
| 70 | desear | regular -ar | yes | yes | yes | yes | PASS |
| 71 | necesitar | regular -ar | yes | yes | yes | yes | PASS |
| 72 | ayudar | regular -ar | yes | yes | yes | yes | PASS |
| 73 | apoyar | regular -ar | yes | yes | yes | yes | PASS |
| 74 | cuidar | regular -ar | yes | yes | yes | yes | PASS |
| 75 | respetar | regular -ar | yes | yes | yes | yes | PASS |
| 76 | controlar | regular -ar | yes | yes | yes | yes | PASS |
| 77 | organizar | regular -ar | yes | yes | yes | yes | PASS |
| 78 | preparar | regular -ar | yes | yes | yes | yes | PASS |
| 79 | participar | regular -ar | yes | yes | yes | yes | PASS |
| 80 | colaborar | regular -ar | yes | yes | yes | yes | PASS |
| 81 | intentar | regular -ar | yes | yes | yes | yes | PASS |
| 82 | lograr | regular -ar | yes | yes | yes | yes | PASS |
| 83 | fallar | regular -ar | yes | yes | yes | yes | PASS |
| 84 | mejorar | regular -ar | yes | yes | yes | yes | PASS |
| 85 | empeorar | regular -ar | yes | yes | yes | yes | PASS |
| 86 | aumentar | regular -ar | yes | yes | yes | yes | PASS |
| 87 | parar | regular -ar | yes | yes | yes | yes | PASS |
| 88 | quedar | regular -ar | yes | yes | yes | yes | PASS |
| 89 | viajar | regular -ar | yes | yes | yes | yes | PASS |
| 90 | caminar | regular -ar | yes | yes | yes | yes | PASS |
| 91 | despertar | irregular -ar | yes | yes | yes | yes | PASS |
| 92 | descansar | regular -ar | yes | yes | yes | yes | PASS |
| 93 | gritar | regular -ar | yes | yes | yes | yes | PASS |
| 94 | arreglar | regular -ar | yes | yes | yes | yes | PASS |
| 95 | tirar | regular -ar | yes | yes | yes | yes | PASS |
| 96 | empujar | regular -ar | yes | yes | yes | yes | PASS |
| 97 | jalar | regular -ar | yes | yes | yes | yes | PASS |
| 98 | temer | regular -er | yes | yes | yes | yes | PASS |
| 99 | correr | regular -er | yes | yes | yes | yes | PASS |
| 100 | entender | irregular -er | yes | yes | yes | yes | PASS |
| 101 | preferir | irregular -ir | yes | yes | yes | yes | PASS |
| 102 | reducir | irregular -ir | yes | yes | yes | yes | PASS |
| 103 | mantener | irregular -er | yes | yes | yes | yes | PASS |
| 104 | resolver | irregular -er | yes | yes | yes | yes | PASS |
| 105 | elegir | irregular -ir | yes | yes | yes | yes | PASS |
| 106 | suponer | irregular -er | yes | yes | yes | yes | PASS |
| 107 | conducir | irregular -ir | yes | yes | yes | yes | PASS |
| 108 | salir | irregular -ir | yes | yes | yes | yes | PASS |
| 109 | volver | irregular -er | yes | yes | yes | yes | PASS |
| 110 | regresar | regular -ar | yes | yes | yes | yes | PASS |
| 111 | sentarse | irregular -ar | yes | yes | yes | yes | PASS |
| 112 | levantarse | regular -ar | yes | yes | yes | yes | PASS |
| 113 | acostarse | irregular -ar | yes | yes | yes | yes | PASS |
| 114 | mover | irregular -er | yes | yes | yes | yes | PASS |
| 115 | reír | irregular -unknown | yes | yes | yes | yes | PASS |
| 116 | sonreír | irregular -unknown | yes | yes | yes | yes | PASS |
| 117 | llorar | regular -ar | yes | yes | yes | yes | PASS |
| 118 | romper | regular -er | yes | yes | yes | yes | PASS |
| 119 | construir | irregular -ir | yes | yes | yes | yes | PASS |
| 120 | destruir | irregular -ir | yes | yes | yes | yes | PASS |
| 121 | llenar | regular -ar | yes | yes | yes | yes | PASS |
| 122 | vaciar | regular -ar | yes | yes | yes | yes | PASS |
| 123 | guardar | regular -ar | yes | yes | yes | yes | PASS |
| 124 | proteger | regular -er | yes | yes | yes | yes | PASS |
| 125 | cocinar | regular -ar | yes | yes | yes | yes | PASS |
| 126 | lavar | regular -ar | yes | yes | yes | yes | PASS |
| 127 | limpiar | regular -ar | yes | yes | yes | yes | PASS |
| 128 | ordenar | regular -ar | yes | yes | yes | yes | PASS |
| 129 | apagar | regular -ar | yes | yes | yes | yes | PASS |
| 130 | conectar | regular -ar | yes | yes | yes | yes | PASS |
| 131 | desconectar | regular -ar | yes | yes | yes | yes | PASS |
| 132 | revisar | regular -ar | yes | yes | yes | yes | PASS |
| 133 | memorizar | regular -ar | yes | yes | yes | yes | PASS |
| 134 | cruzar | regular -ar | yes | yes | yes | yes | PASS |
| 135 | pintar | regular -ar | yes | yes | yes | yes | PASS |
| 136 | dibujar | regular -ar | yes | yes | yes | yes | PASS |
| 137 | cortar | regular -ar | yes | yes | yes | yes | PASS |
| 138 | pegar | regular -ar | yes | yes | yes | yes | PASS |
| 139 | mezclar | regular -ar | yes | yes | yes | yes | PASS |
| 140 | preguntar | regular -ar | yes | yes | yes | yes | PASS |
| 141 | contestar | regular -ar | yes | yes | yes | yes | PASS |
| 142 | narrar | regular -ar | yes | yes | yes | yes | PASS |
| 143 | discutir | regular -ir | yes | yes | yes | yes | PASS |
| 144 | aclarar | regular -ar | yes | yes | yes | yes | PASS |
| 145 | saludar | regular -ar | yes | yes | yes | yes | PASS |
| 146 | invitar | regular -ar | yes | yes | yes | yes | PASS |
| 147 | avisar | regular -ar | yes | yes | yes | yes | PASS |
| 148 | confirmar | regular -ar | yes | yes | yes | yes | PASS |
| 149 | cancelar | regular -ar | yes | yes | yes | yes | PASS |
| 150 | enseñar | regular -ar | yes | yes | yes | yes | PASS |
| 151 | presentar | regular -ar | yes | yes | yes | yes | PASS |
| 152 | beber | regular -er | yes | yes | yes | yes | PASS |
| 153 | responder | regular -er | yes | yes | yes | yes | PASS |
| 154 | coser | regular -er | yes | yes | yes | yes | PASS |
| 155 | prometer | regular -er | yes | yes | yes | yes | PASS |
| 156 | describir | regular -ir | yes | yes | yes | yes | PASS |
| 157 | practicar | regular -ar | yes | yes | yes | yes | PASS |
| 158 | alcanzar | regular -ar | yes | yes | yes | yes | PASS |
| 159 | encender | irregular -er | yes | yes | yes | yes | PASS |
| 160 | probar | irregular -ar | yes | yes | yes | yes | PASS |
| 161 | repetir | irregular -ir | yes | yes | yes | yes | PASS |
| 162 | servir | irregular -ir | yes | yes | yes | yes | PASS |
| 163 | calentar | irregular -ar | yes | yes | yes | yes | PASS |
| 164 | perseguir | irregular -ir | yes | yes | yes | yes | PASS |
| 165 | medir | irregular -ir | yes | yes | yes | yes | PASS |
| 166 | despedirse | irregular -ir | yes | yes | yes | yes | PASS |
| 167 | rechazar | regular -ar | yes | yes | yes | yes | PASS |
| 168 | advertir | irregular -ir | yes | yes | yes | yes | PASS |
| 169 | corregir | irregular -ir | yes | yes | yes | yes | PASS |
| 170 | evaluar | irregular -ar | yes | yes | yes | yes | PASS |
| 171 | aprobar | irregular -ar | yes | yes | yes | yes | PASS |
| 172 | reprobar | irregular -ar | yes | yes | yes | yes | PASS |
| 173 | exponer | irregular -er | yes | yes | yes | yes | PASS |
| 174 | acercarse | regular -ar | yes | yes | yes | yes | PASS |
| 175 | alejarse | regular -ar | yes | yes | yes | yes | PASS |
| 176 | dudar | regular -ar | yes | yes | yes | yes | PASS |
| 177 | notar | regular -ar | yes | yes | yes | yes | PASS |
| 178 | emocionarse | regular -ar | yes | yes | yes | yes | PASS |
| 179 | preocuparse | regular -ar | yes | yes | yes | yes | PASS |
| 180 | amar | regular -ar | yes | yes | yes | yes | PASS |
| 181 | odiar | regular -ar | yes | yes | yes | yes | PASS |
| 182 | molestar | regular -ar | yes | yes | yes | yes | PASS |
| 183 | importar | regular -ar | yes | yes | yes | yes | PASS |
| 184 | afectar | regular -ar | yes | yes | yes | yes | PASS |
| 185 | sospechar | regular -ar | yes | yes | yes | yes | PASS |
| 186 | explorar | regular -ar | yes | yes | yes | yes | PASS |
| 187 | conservar | regular -ar | yes | yes | yes | yes | PASS |
| 188 | abandonar | regular -ar | yes | yes | yes | yes | PASS |
| 189 | recuperar | regular -ar | yes | yes | yes | yes | PASS |
| 190 | hallar | regular -ar | yes | yes | yes | yes | PASS |
| 191 | continuar | regular -ar | yes | yes | yes | yes | PASS |
| 192 | gustar | regular -ar | yes | yes | yes | yes | PASS |
| 193 | encantar | regular -ar | yes | yes | yes | yes | PASS |
| 194 | confiar | irregular -ar | yes | yes | yes | yes | PASS |
| 195 | arriesgar | regular -ar | yes | yes | yes | yes | PASS |
| 196 | rendirse | irregular -ir | yes | yes | yes | yes | PASS |
| 197 | nacer | irregular -er | yes | yes | yes | yes | PASS |
| 198 | crecer | irregular -er | yes | yes | yes | yes | PASS |
| 199 | morir | irregular -ir | yes | yes | yes | yes | PASS |
| 200 | detener | irregular -er | yes | yes | yes | yes | PASS |
| 201 | partir | regular -ir | yes | yes | yes | yes | PASS |
| 202 | mudarse | regular -ar | yes | yes | yes | yes | PASS |
| 203 | descubrir | regular -ir | yes | yes | yes | yes | PASS |
| 204 | conocer | irregular -er | yes | yes | yes | yes | PASS |
| 205 | reconocer | irregular -er | yes | yes | yes | yes | PASS |
| 206 | fabricar | regular -ar | yes | yes | yes | yes | PASS |
| 207 | desarrollar | regular -ar | yes | yes | yes | yes | PASS |
| 208 | diseñar | regular -ar | yes | yes | yes | yes | PASS |
| 209 | planificar | regular -ar | yes | yes | yes | yes | PASS |
| 210 | coordinar | regular -ar | yes | yes | yes | yes | PASS |
| 211 | administrar | regular -ar | yes | yes | yes | yes | PASS |
| 212 | gestionar | regular -ar | yes | yes | yes | yes | PASS |
| 213 | supervisar | regular -ar | yes | yes | yes | yes | PASS |
| 214 | analizar | regular -ar | yes | yes | yes | yes | PASS |
| 215 | comparar | regular -ar | yes | yes | yes | yes | PASS |
| 216 | calcular | regular -ar | yes | yes | yes | yes | PASS |
| 217 | estimar | regular -ar | yes | yes | yes | yes | PASS |
| 218 | ahorrar | regular -ar | yes | yes | yes | yes | PASS |
| 219 | gastar | regular -ar | yes | yes | yes | yes | PASS |
| 220 | cobrar | regular -ar | yes | yes | yes | yes | PASS |
| 221 | negociar | regular -ar | yes | yes | yes | yes | PASS |
| 222 | contratar | regular -ar | yes | yes | yes | yes | PASS |
| 223 | editar | regular -ar | yes | yes | yes | yes | PASS |
| 224 | actualizar | regular -ar | yes | yes | yes | yes | PASS |
| 225 | archivar | regular -ar | yes | yes | yes | yes | PASS |
| 226 | registrar | regular -ar | yes | yes | yes | yes | PASS |
| 227 | documentar | regular -ar | yes | yes | yes | yes | PASS |
| 228 | informar | regular -ar | yes | yes | yes | yes | PASS |
| 229 | reportar | regular -ar | yes | yes | yes | yes | PASS |
| 230 | observar | regular -ar | yes | yes | yes | yes | PASS |
| 231 | detectar | regular -ar | yes | yes | yes | yes | PASS |
| 232 | agarrar | regular -ar | yes | yes | yes | yes | PASS |
| 233 | sujetar | regular -ar | yes | yes | yes | yes | PASS |
| 234 | golpear | regular -ar | yes | yes | yes | yes | PASS |
| 235 | rozar | regular -ar | yes | yes | yes | yes | PASS |
| 236 | presionar | regular -ar | yes | yes | yes | yes | PASS |
| 237 | separar | regular -ar | yes | yes | yes | yes | PASS |
| 238 | relacionar | regular -ar | yes | yes | yes | yes | PASS |
| 239 | acusar | regular -ar | yes | yes | yes | yes | PASS |
| 240 | justificar | regular -ar | yes | yes | yes | yes | PASS |
| 241 | argumentar | regular -ar | yes | yes | yes | yes | PASS |
| 242 | cooperar | regular -ar | yes | yes | yes | yes | PASS |
| 243 | involucrar | regular -ar | yes | yes | yes | yes | PASS |
| 244 | transformar | regular -ar | yes | yes | yes | yes | PASS |
| 245 | modificar | regular -ar | yes | yes | yes | yes | PASS |
| 246 | alterar | regular -ar | yes | yes | yes | yes | PASS |
| 247 | adaptar | regular -ar | yes | yes | yes | yes | PASS |
| 248 | ajustar | regular -ar | yes | yes | yes | yes | PASS |
| 249 | optimizar | regular -ar | yes | yes | yes | yes | PASS |
| 250 | deteriorar | regular -ar | yes | yes | yes | yes | PASS |
| 251 | causar | regular -ar | yes | yes | yes | yes | PASS |
| 252 | provocar | regular -ar | yes | yes | yes | yes | PASS |
| 253 | generar | regular -ar | yes | yes | yes | yes | PASS |
| 254 | impactar | regular -ar | yes | yes | yes | yes | PASS |
| 255 | dominar | regular -ar | yes | yes | yes | yes | PASS |
| 256 | manejar | regular -ar | yes | yes | yes | yes | PASS |
| 257 | limitar | regular -ar | yes | yes | yes | yes | PASS |
| 258 | ceder | regular -er | yes | yes | yes | yes | PASS |
| 259 | convencer | regular -er | yes | yes | yes | yes | PASS |
| 260 | cumplir | regular -ir | yes | yes | yes | yes | PASS |
| 261 | resistir | regular -ir | yes | yes | yes | yes | PASS |
| 262 | expandir | regular -ir | yes | yes | yes | yes | PASS |
| 263 | restringir | regular -ir | yes | yes | yes | yes | PASS |
| 264 | producir | irregular -ir | yes | yes | yes | yes | PASS |
| 265 | dirigir | regular -ir | yes | yes | yes | yes | PASS |
| 266 | invertir | irregular -ir | yes | yes | yes | yes | PASS |
| 267 | despedir | irregular -ir | yes | yes | yes | yes | PASS |
| 268 | publicar | regular -ar | yes | yes | yes | yes | PASS |
| 269 | oír | irregular -unknown | yes | yes | yes | yes | PASS |
| 270 | percibir | regular -ir | yes | yes | yes | yes | PASS |
| 271 | tocar | regular -ar | yes | yes | yes | yes | PASS |
| 272 | soltar | irregular -ar | yes | yes | yes | yes | PASS |
| 273 | apretar | irregular -ar | yes | yes | yes | yes | PASS |
| 274 | unir | regular -ir | yes | yes | yes | yes | PASS |
| 275 | dividir | regular -ir | yes | yes | yes | yes | PASS |
| 276 | obedecer | irregular -er | yes | yes | yes | yes | PASS |
| 277 | violar | regular -ar | yes | yes | yes | yes | PASS |
| 278 | exigir | regular -ir | yes | yes | yes | yes | PASS |
| 279 | prohibir | irregular -ir | yes | yes | yes | yes | PASS |
| 280 | defender | irregular -er | yes | yes | yes | yes | PASS |
| 281 | influir | irregular -ir | yes | yes | yes | yes | PASS |
| 282 | competir | irregular -ir | yes | yes | yes | yes | PASS |
| 283 | oponerse | irregular -er | yes | yes | yes | yes | PASS |
| 284 | conseguir | irregular -ir | yes | yes | yes | yes | PASS |
| 285 | regular | regular -ar | yes | yes | yes | yes | PASS |
| 286 | persuadir | regular -ir | yes | yes | yes | yes | PASS |
| 287 | reflexionar | regular -ar | yes | yes | yes | yes | PASS |
| 288 | razonar | regular -ar | yes | yes | yes | yes | PASS |
| 289 | valorar | regular -ar | yes | yes | yes | yes | PASS |
| 290 | interpretar | regular -ar | yes | yes | yes | yes | PASS |
| 291 | plantear | regular -ar | yes | yes | yes | yes | PASS |
| 292 | formular | regular -ar | yes | yes | yes | yes | PASS |
| 293 | cuestionar | regular -ar | yes | yes | yes | yes | PASS |
| 294 | criticar | regular -ar | yes | yes | yes | yes | PASS |
| 295 | verificar | regular -ar | yes | yes | yes | yes | PASS |
| 296 | descartar | regular -ar | yes | yes | yes | yes | PASS |
| 297 | afirmar | regular -ar | yes | yes | yes | yes | PASS |
| 298 | programar | regular -ar | yes | yes | yes | yes | PASS |
| 299 | codificar | regular -ar | yes | yes | yes | yes | PASS |
| 300 | configurar | regular -ar | yes | yes | yes | yes | PASS |
| 301 | instalar | regular -ar | yes | yes | yes | yes | PASS |
| 302 | borrar | regular -ar | yes | yes | yes | yes | PASS |
| 303 | enviar | regular -ar | yes | yes | yes | yes | PASS |
| 304 | copiar | regular -ar | yes | yes | yes | yes | PASS |
| 305 | navegar | regular -ar | yes | yes | yes | yes | PASS |
| 306 | iniciar | regular -ar | yes | yes | yes | yes | PASS |
| 307 | bloquear | regular -ar | yes | yes | yes | yes | PASS |
| 308 | desbloquear | regular -ar | yes | yes | yes | yes | PASS |
| 309 | pactar | regular -ar | yes | yes | yes | yes | PASS |
| 310 | debatir | regular -ir | yes | yes | yes | yes | PASS |
| 311 | dialogar | regular -ar | yes | yes | yes | yes | PASS |
| 312 | mediar | regular -ar | yes | yes | yes | yes | PASS |
| 313 | arbitrar | regular -ar | yes | yes | yes | yes | PASS |
| 314 | conciliar | regular -ar | yes | yes | yes | yes | PASS |
| 315 | atacar | regular -ar | yes | yes | yes | yes | PASS |
| 316 | amenazar | regular -ar | yes | yes | yes | yes | PASS |
| 317 | castigar | regular -ar | yes | yes | yes | yes | PASS |
| 318 | sancionar | regular -ar | yes | yes | yes | yes | PASS |
| 319 | premiar | regular -ar | yes | yes | yes | yes | PASS |
| 320 | recompensar | regular -ar | yes | yes | yes | yes | PASS |
| 321 | compensar | regular -ar | yes | yes | yes | yes | PASS |
| 322 | multar | regular -ar | yes | yes | yes | yes | PASS |
| 323 | evolucionar | regular -ar | yes | yes | yes | yes | PASS |
| 324 | desarrollarse | regular -ar | yes | yes | yes | yes | PASS |
| 325 | transformarse | regular -ar | yes | yes | yes | yes | PASS |
| 326 | adaptarse | regular -ar | yes | yes | yes | yes | PASS |
| 327 | marcar | regular -ar | yes | yes | yes | yes | PASS |
| 328 | determinar | regular -ar | yes | yes | yes | yes | PASS |
| 329 | condicionar | regular -ar | yes | yes | yes | yes | PASS |
| 330 | orientar | regular -ar | yes | yes | yes | yes | PASS |
| 331 | guiar | regular -ar | yes | yes | yes | yes | PASS |
| 332 | inspirar | regular -ar | yes | yes | yes | yes | PASS |
| 333 | motivar | regular -ar | yes | yes | yes | yes | PASS |
| 334 | aportar | regular -ar | yes | yes | yes | yes | PASS |
| 335 | heredar | regular -ar | yes | yes | yes | yes | PASS |
| 336 | preservar | regular -ar | yes | yes | yes | yes | PASS |
| 337 | comprender | regular -er | yes | yes | yes | yes | PASS |
| 338 | someter | regular -er | yes | yes | yes | yes | PASS |
| 339 | acceder | regular -er | yes | yes | yes | yes | PASS |
| 340 | asumir | regular -ir | yes | yes | yes | yes | PASS |
| 341 | compartir | regular -ir | yes | yes | yes | yes | PASS |
| 342 | difundir | regular -ir | yes | yes | yes | yes | PASS |
| 343 | transmitir | regular -ir | yes | yes | yes | yes | PASS |
| 344 | persistir | regular -ir | yes | yes | yes | yes | PASS |
| 345 | definir | regular -ir | yes | yes | yes | yes | PASS |
| 346 | imprimir | regular -ir | yes | yes | yes | yes | PASS |
| 347 | recibir | regular -ir | yes | yes | yes | yes | PASS |
| 348 | deducir | irregular -ir | yes | yes | yes | yes | PASS |
| 349 | inferir | irregular -ir | yes | yes | yes | yes | PASS |
| 350 | concluir | irregular -ir | yes | yes | yes | yes | PASS |
| 351 | proponer | irregular -er | yes | yes | yes | yes | PASS |
| 352 | sugerir | irregular -ir | yes | yes | yes | yes | PASS |
| 353 | comprobar | irregular -ar | yes | yes | yes | yes | PASS |
| 354 | negar | irregular -ar | yes | yes | yes | yes | PASS |
| 355 | descargar | regular -ar | yes | yes | yes | yes | PASS |
| 356 | eliminar | regular -ar | yes | yes | yes | yes | PASS |
| 357 | registrarse | regular -ar | yes | yes | yes | yes | PASS |
| 358 | imponer | irregular -er | yes | yes | yes | yes | PASS |
| 359 | indemnizar | regular -ar | yes | yes | yes | yes | PASS |
| 360 | desaparecer | irregular -er | yes | yes | yes | yes | PASS |
| 361 | surgir | regular -ir | yes | yes | yes | yes | PASS |
| 362 | emerger | regular -er | yes | yes | yes | yes | PASS |
| 363 | contribuir | irregular -ir | yes | yes | yes | yes | PASS |
| 364 | trascender | irregular -er | yes | yes | yes | yes | PASS |
| 365 | acordar | irregular -ar | yes | yes | yes | yes | PASS |
| 366 | cultivar | regular -ar | yes | yes | yes | yes | PASS |
| 367 | cosechar | regular -ar | yes | yes | yes | yes | PASS |
| 368 | podar | regular -ar | yes | yes | yes | yes | PASS |
| 369 | fertilizar | regular -ar | yes | yes | yes | yes | PASS |
| 370 | contaminar | regular -ar | yes | yes | yes | yes | PASS |
| 371 | reciclar | regular -ar | yes | yes | yes | yes | PASS |
| 372 | reutilizar | regular -ar | yes | yes | yes | yes | PASS |
| 373 | migrar | regular -ar | yes | yes | yes | yes | PASS |
| 374 | habitar | regular -ar | yes | yes | yes | yes | PASS |
| 375 | ocupar | regular -ar | yes | yes | yes | yes | PASS |
| 376 | desplazarse | regular -ar | yes | yes | yes | yes | PASS |
| 377 | asentarse | regular -ar | yes | yes | yes | yes | PASS |
| 378 | marchitarse | regular -ar | yes | yes | yes | yes | PASS |
| 379 | pausar | regular -ar | yes | yes | yes | yes | PASS |
| 380 | alternar | regular -ar | yes | yes | yes | yes | PASS |
| 381 | acelerar | regular -ar | yes | yes | yes | yes | PASS |
| 382 | frenar | regular -ar | yes | yes | yes | yes | PASS |
| 383 | anticipar | regular -ar | yes | yes | yes | yes | PASS |
| 384 | aplazar | regular -ar | yes | yes | yes | yes | PASS |
| 385 | retrasar | regular -ar | yes | yes | yes | yes | PASS |
| 386 | adelantar | regular -ar | yes | yes | yes | yes | PASS |
| 387 | durar | regular -ar | yes | yes | yes | yes | PASS |
| 388 | progresar | regular -ar | yes | yes | yes | yes | PASS |
| 389 | estancarse | regular -ar | yes | yes | yes | yes | PASS |
| 390 | planear | regular -ar | yes | yes | yes | yes | PASS |
| 391 | actuar | regular -ar | yes | yes | yes | yes | PASS |
| 392 | cantar | regular -ar | yes | yes | yes | yes | PASS |
| 393 | bailar | regular -ar | yes | yes | yes | yes | PASS |
| 394 | emocionar | regular -ar | yes | yes | yes | yes | PASS |
| 395 | sorprender | regular -er | yes | yes | yes | yes | PASS |
| 396 | aburrir | regular -ir | yes | yes | yes | yes | PASS |
| 397 | estimular | regular -ar | yes | yes | yes | yes | PASS |
| 398 | significar | regular -ar | yes | yes | yes | yes | PASS |
| 399 | simbolizar | regular -ar | yes | yes | yes | yes | PASS |
| 400 | revelar | regular -ar | yes | yes | yes | yes | PASS |
| 401 | ocultar | regular -ar | yes | yes | yes | yes | PASS |
| 402 | comunicar | regular -ar | yes | yes | yes | yes | PASS |
| 403 | inventar | regular -ar | yes | yes | yes | yes | PASS |
| 404 | expresar | regular -ar | yes | yes | yes | yes | PASS |
| 405 | contemplar | regular -ar | yes | yes | yes | yes | PASS |
| 406 | meditar | regular -ar | yes | yes | yes | yes | PASS |
| 407 | representar | regular -ar | yes | yes | yes | yes | PASS |
| 408 | suceder | regular -er | yes | yes | yes | yes | PASS |
| 409 | ocurrir | regular -ir | yes | yes | yes | yes | PASS |
| 410 | sobrevivir | regular -ir | yes | yes | yes | yes | PASS |
| 411 | esculpir | regular -ir | yes | yes | yes | yes | PASS |
| 412 | florecer | irregular -er | yes | yes | yes | yes | PASS |
| 413 | germinar | regular -ar | yes | yes | yes | yes | PASS |
| 414 | sembrar | irregular -ar | yes | yes | yes | yes | PASS |
| 415 | regar | irregular -ar | yes | yes | yes | yes | PASS |
| 416 | variar | irregular -ar | yes | yes | yes | yes | PASS |
| 417 | prever | irregular -er | yes | yes | yes | yes | PASS |
| 418 | posponer | irregular -er | yes | yes | yes | yes | PASS |
| 419 | transcurrir | regular -ir | yes | yes | yes | yes | PASS |
| 420 | componer | irregular -er | yes | yes | yes | yes | PASS |
| 421 | entretener | irregular -er | yes | yes | yes | yes | PASS |
| 422 | distraer | irregular -er | yes | yes | yes | yes | PASS |
| 423 | conmover | irregular -er | yes | yes | yes | yes | PASS |
| 424 | referir | irregular -ir | yes | yes | yes | yes | PASS |
| 425 | juzgar | regular -ar | yes | yes | yes | yes | PASS |
| 426 | experimentar | regular -ar | yes | yes | yes | yes | PASS |
| 427 | clasificar | regular -ar | yes | yes | yes | yes | PASS |
| 428 | modelar | regular -ar | yes | yes | yes | yes | PASS |
| 429 | simular | regular -ar | yes | yes | yes | yes | PASS |
| 430 | legislar | regular -ar | yes | yes | yes | yes | PASS |
| 431 | vigilar | regular -ar | yes | yes | yes | yes | PASS |
| 432 | auditar | regular -ar | yes | yes | yes | yes | PASS |
| 433 | investigar | regular -ar | yes | yes | yes | yes | PASS |
| 434 | sentenciar | regular -ar | yes | yes | yes | yes | PASS |
| 435 | demostrar | irregular -ar | yes | yes | yes | yes | PASS |
| 436 | gobernar | irregular -ar | yes | yes | yes | yes | PASS |
| 437 | predecir | irregular -ir | yes | yes | yes | yes | PASS |
| 438 | absolver | irregular -er | yes | yes | yes | yes | PASS |
| 439 | concebir | irregular -ir | yes | yes | yes | yes | PASS |
| 440 | abordar | regular -ar | yes | yes | yes | yes | PASS |
| 441 | adoptar | regular -ar | yes | yes | yes | yes | PASS |
| 442 | afinar | regular -ar | yes | yes | yes | yes | PASS |
| 443 | aplicar | regular -ar | yes | yes | yes | yes | PASS |
| 444 | arrear | regular -ar | yes | yes | yes | yes | PASS |
| 445 | asar | regular -ar | yes | yes | yes | yes | PASS |
| 446 | brillar | regular -ar | yes | yes | yes | yes | PASS |
| 447 | bucear | regular -ar | yes | yes | yes | yes | PASS |
| 448 | callar | regular -ar | yes | yes | yes | yes | PASS |
| 449 | captar | regular -ar | yes | yes | yes | yes | PASS |
| 450 | categorizar | regular -ar | yes | yes | yes | yes | PASS |
| 451 | cenar | regular -ar | yes | yes | yes | yes | PASS |
| 452 | concentrar | regular -ar | yes | yes | yes | yes | PASS |
| 453 | contraargumentar | regular -ar | yes | yes | yes | yes | PASS |
| 454 | contrastar | regular -ar | yes | yes | yes | yes | PASS |
| 455 | conversar | regular -ar | yes | yes | yes | yes | PASS |
| 456 | decorar | regular -ar | yes | yes | yes | yes | PASS |
| 457 | denunciar | regular -ar | yes | yes | yes | yes | PASS |
| 458 | desayunar | regular -ar | yes | yes | yes | yes | PASS |
| 459 | destacar | regular -ar | yes | yes | yes | yes | PASS |
| 460 | diversificar | regular -ar | yes | yes | yes | yes | PASS |
| 461 | doblar | regular -ar | yes | yes | yes | yes | PASS |
| 462 | enamorar | regular -ar | yes | yes | yes | yes | PASS |
| 463 | escampar | regular -ar | yes | yes | yes | yes | PASS |
| 464 | escapar | regular -ar | yes | yes | yes | yes | PASS |
| 465 | evidenciar | regular -ar | yes | yes | yes | yes | PASS |
| 466 | extrañar | regular -ar | yes | yes | yes | yes | PASS |
| 467 | funcionar | regular -ar | yes | yes | yes | yes | PASS |
| 468 | identificar | regular -ar | yes | yes | yes | yes | PASS |
| 469 | implementar | regular -ar | yes | yes | yes | yes | PASS |
| 470 | inaugurar | regular -ar | yes | yes | yes | yes | PASS |
| 471 | indicar | regular -ar | yes | yes | yes | yes | PASS |
| 472 | manipular | regular -ar | yes | yes | yes | yes | PASS |
| 473 | montar | regular -ar | yes | yes | yes | yes | PASS |
| 474 | nadar | regular -ar | yes | yes | yes | yes | PASS |
| 475 | nombrar | regular -ar | yes | yes | yes | yes | PASS |
| 476 | opinar | regular -ar | yes | yes | yes | yes | PASS |
| 477 | pelear | regular -ar | yes | yes | yes | yes | PASS |
| 478 | pregonar | regular -ar | yes | yes | yes | yes | PASS |
| 479 | preocupar | regular -ar | yes | yes | yes | yes | PASS |
| 480 | quemar | regular -ar | yes | yes | yes | yes | PASS |
| 481 | reclamar | regular -ar | yes | yes | yes | yes | PASS |
| 482 | reparar | regular -ar | yes | yes | yes | yes | PASS |
| 483 | rescatar | regular -ar | yes | yes | yes | yes | PASS |
| 484 | resultar | regular -ar | yes | yes | yes | yes | PASS |
| 485 | resumir | regular -ir | yes | yes | yes | yes | PASS |
| 486 | retomar | regular -ar | yes | yes | yes | yes | PASS |
| 487 | reformular | regular -ar | yes | yes | yes | yes | PASS |
| 488 | sintetizar | regular -ar | yes | yes | yes | yes | PASS |
| 489 | solicitar | regular -ar | yes | yes | yes | yes | PASS |
| 490 | surfear | regular -ar | yes | yes | yes | yes | PASS |
| 491 | tolerar | regular -ar | yes | yes | yes | yes | PASS |
| 492 | tomar | regular -ar | yes | yes | yes | yes | PASS |
| 493 | triunfar | regular -ar | yes | yes | yes | yes | PASS |
| 494 | visitar | regular -ar | yes | yes | yes | yes | PASS |
| 495 | vislumbrar | regular -ar | yes | yes | yes | yes | PASS |
| 496 | conjugar | regular -ar | yes | yes | yes | yes | PASS |
| 497 | declarar | regular -ar | yes | yes | yes | yes | PASS |
| 498 | amplificar | regular -ar | yes | yes | yes | yes | PASS |
| 499 | pescar | regular -ar | yes | yes | yes | yes | PASS |
| 500 | madrugar | regular -ar | yes | yes | yes | yes | PASS |
| 501 | luchar | regular -ar | yes | yes | yes | yes | PASS |
| 502 | autorizar | regular -ar | yes | yes | yes | yes | PASS |
| 503 | garantizar | regular -ar | yes | yes | yes | yes | PASS |
| 504 | gozar | regular -ar | yes | yes | yes | yes | PASS |
| 505 | reubicar | regular -ar | yes | yes | yes | yes | PASS |
| 506 | cabalgar | regular -ar | yes | yes | yes | yes | PASS |
| 507 | costar | irregular -ar | yes | yes | yes | yes | PASS |
| 508 | sonar | irregular -ar | yes | yes | yes | yes | PASS |
| 509 | tostar | irregular -ar | yes | yes | yes | yes | PASS |
| 510 | volar | irregular -ar | yes | yes | yes | yes | PASS |
| 511 | almorzar | irregular -ar | yes | yes | yes | yes | PASS |
| 512 | comenzar | irregular -ar | yes | yes | yes | yes | PASS |
| 513 | nevar | irregular -ar | yes | yes | yes | yes | PASS |
| 514 | jugar | irregular -ar | yes | yes | yes | yes | PASS |
| 515 | agradecer | irregular -er | yes | yes | yes | yes | PASS |
| 516 | amanecer | irregular -er | yes | yes | yes | yes | PASS |
| 517 | atardecer | irregular -er | yes | yes | yes | yes | PASS |
| 518 | establecer | irregular -er | yes | yes | yes | yes | PASS |
| 519 | fallecer | irregular -er | yes | yes | yes | yes | PASS |
| 520 | pertenecer | irregular -er | yes | yes | yes | yes | PASS |
| 521 | renacer | irregular -er | yes | yes | yes | yes | PASS |
| 522 | comprometer | regular -er | yes | yes | yes | yes | PASS |
| 523 | recorrer | regular -er | yes | yes | yes | yes | PASS |
| 524 | pesar | regular -ar | yes | yes | yes | yes | PASS |
| 525 | atender | irregular -er | yes | yes | yes | yes | PASS |
| 526 | moler | irregular -er | yes | yes | yes | yes | PASS |
| 527 | morder | irregular -er | yes | yes | yes | yes | PASS |
| 528 | llover | irregular -er | yes | yes | yes | yes | PASS |
| 529 | ejercer | regular -er | yes | yes | yes | yes | PASS |
| 530 | recoger | regular -er | yes | yes | yes | yes | PASS |
| 531 | aludir | regular -ir | yes | yes | yes | yes | PASS |
| 532 | asistir | regular -ir | yes | yes | yes | yes | PASS |
| 533 | fingir | regular -ir | yes | yes | yes | yes | PASS |
| 534 | incumplir | regular -ir | yes | yes | yes | yes | PASS |
| 535 | insistir | regular -ir | yes | yes | yes | yes | PASS |
| 536 | remitir | regular -ir | yes | yes | yes | yes | PASS |
| 537 | incluir | irregular -ir | yes | yes | yes | yes | PASS |
| 538 | constituir | irregular -ir | yes | yes | yes | yes | PASS |
| 539 | diluir | irregular -ir | yes | yes | yes | yes | PASS |
| 540 | distribuir | irregular -ir | yes | yes | yes | yes | PASS |
| 541 | hervir | irregular -ir | yes | yes | yes | yes | PASS |
| 542 | requerir | irregular -ir | yes | yes | yes | yes | PASS |
| 543 | transferir | irregular -ir | yes | yes | yes | yes | PASS |
| 544 | intervenir | irregular -ir | yes | yes | yes | yes | PASS |
| 545 | prevenir | irregular -ir | yes | yes | yes | yes | PASS |
| 546 | convenir | irregular -ir | yes | yes | yes | yes | PASS |
| 547 | disponer | irregular -er | yes | yes | yes | yes | PASS |
| 548 | sostener | irregular -er | yes | yes | yes | yes | PASS |
| 549 | satisfacer | irregular -er | yes | yes | yes | yes | PASS |
| 550 | traducir | irregular -ir | yes | yes | yes | yes | PASS |
| 551 | traer | irregular -er | yes | yes | yes | yes | PASS |
| 552 | pedir | irregular -ir | yes | yes | yes | yes | PASS |
| 553 | reescribir | regular -ir | yes | yes | yes | yes | PASS |
| 554 | moverse | irregular -er | yes | yes | yes | yes | PASS |
| 555 | quedarse | regular -ar | yes | yes | yes | yes | PASS |
| 556 | atenuar | irregular -ar | yes | yes | yes | yes | PASS |
| 557 | girar | regular -ar | yes | yes | yes | yes | PASS |
| 558 | implicar | regular -ar | yes | yes | yes | yes | PASS |
| 559 | secar | regular -ar | yes | yes | yes | yes | PASS |
| 560 | corroborar | regular -ar | yes | yes | yes | yes | PASS |
| 561 | reflejar | regular -ar | yes | yes | yes | yes | PASS |
| 562 | tejer | regular -er | yes | yes | yes | yes | PASS |

## Failures

**None.** All 562 verbs passed all 5 validation checks.

## Validation checks performed

1. **No vos** — JSON string scan for `"vos"` in entire processed entry
2. **Negative persons complete** — all 5 IMPERATIVE_PERSONS in `imperative.negative`
3. **Negative derivation** — `negative[p] == "no " + subjunctive.present[subj_key_for_person(p)]`
4. **Forms traceable** — indicative/subjunctive forms match source via PERSON_MAP_FULL
5. **Metadata present** — `postprocess_meta.negative_imperative_derived == True`

## Structural changes applied

| Change | Detail |
|--------|--------|
| Mood keys | indicativo→indicative, subjuntivo→subjunctive, imperativo→imperative |
| Tense keys | presente→present, pretérito indefinido→preterite, pretérito imperfecto→imperfect, futuro→future, condicional→conditional, afirmativo→affirmative |
| Person keys (ind/subj) | él→él/ella/usted, ellos→ellos/ellas/ustedes, vosotros→null (new slot) |
| Person keys (imperative) | él→usted, ustedes→ustedes, vosotros→null (new slot) |
| Removed | vos (all tenses) |
| Added fields | lemma, verb_type, postprocess_meta |
| Added tense | imperative.negative (derived from subjunctive.present) |
| normalize_structure | PERSONS_FULL setdefault(None) on ALL tables (incl. imperative) |

## Scale safety assessment

**SAFE TO SCALE.** The post-processor:
- Matches the agreed VerbEntry spec (lemma, verb_type, conjugations, postprocess_meta)
- Uses English mood/tense keys throughout
- Uses compound person labels (él/ella/usted, ellos/ellas/ustedes)
- PERSONS_FULL padded into all tables via ensure_all_persons_exist
- Negative imperative derived via subj_key_for_person() — no forms invented
- validate_final_structure passes for all verbs
- remove_disallowed_variants removes vos/voseo/archaic recursively
- Ready to apply to all 562 verb lemmas.
