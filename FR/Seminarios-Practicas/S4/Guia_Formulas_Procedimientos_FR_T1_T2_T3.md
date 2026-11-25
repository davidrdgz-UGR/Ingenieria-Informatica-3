# GUÍA DE FÓRMULAS Y PROCEDIMIENTOS — FUNDAMENTOS DE REDES
## Temas 1, 2 y 3 · Repaso de los ejercicios trabajados

**Objetivo:** identificar el tipo de problema, elegir las fórmulas apropiadas y saber en qué orden aplicarlas. Se distinguen las **fórmulas generales** de las **hipótesis particulares** del Seminario 5 del Tema 3.

---

# 0. Conversiones y notación

| Símbolo | Significado | Unidad habitual |
|---|---|---|
| `L`, `M` | Longitud de datos/mensaje | bits |
| `P` | Tamaño total de paquete | bits o bytes |
| `H` | Tamaño de la cabecera | bits o bytes |
| `V`, `R` | Velocidad de transmisión | bits/s |
| `D`, `m` | Distancia | m |
| `s`, `v_p` | Velocidad de propagación | m/s |
| `t_t` | Retardo de transmisión | s o ms |
| `t_p` | Retardo de propagación | s o ms |
| `RTT` | Tiempo de ida y vuelta | s o ms |
| `MSS` | Datos de un segmento TCP | bytes |
| `CWIN` / `CongWin` | Ventana de congestión | MSS o bytes |
| `RWIN` | Ventana ofertada por el receptor | MSS o bytes |

- `1 byte = 8 bits`.
- `1 Mbps = 10^6 bits/s`; `1 Gbps = 10^9 bits/s`.
- `1 ms = 10^-3 s`; `1 km = 1.000 m`.
- Usa **unidades coherentes** antes de sustituir; redondea preferiblemente al final.
- `ceil(x)` redondea **hacia arriba**; `floor(x)` redondea **hacia abajo**.

---

# TEMA 1 — Introducción: transmisión y propagación

## 1. Retardo de transmisión (`t_t`)

**Cuándo:** te dan una cantidad de bits y la velocidad del enlace.

```text
t_t = L / V
```

- `L`: bits **realmente transmitidos**, incluyendo cabeceras si existen.
- `V`: bits por segundo.

**Procedimiento:** (1) convierte bytes a bits; (2) convierte Mbps a bps; (3) divide; (4) expresa en ms si procede.

**Ejemplo:** 800.000 bits a 2 Mbps → `t_t = 800.000 / 2.000.000 = 0,4 s`.

## 2. Retardo de propagación (`t_p`)

**Cuándo:** te dan distancia y velocidad de propagación del medio.

```text
t_p = D / s
```

**Ejemplo:** 20.000 km con velocidad `2,5·10^8 m/s` → `t_p = 20.000.000 / 250.000.000 = 0,08 s`.

**Diferencia crucial:** la transmisión depende de `L` y `V`; la propagación depende de `D` y `s`.

## 3. Retardo total entre dos extremos

En **un enlace**, si no hay otros retardos:

```text
T_total = t_t + t_p
```

Si hay procesamiento, colas o acceso al medio:

```text
T_total = Σ(t_transmisión + t_propagación + t_procesamiento + t_cola + t_acceso)
```

**Procedimiento:** dibuja los enlaces, identifica qué retardos ocurren en cada uno y súmalos. Los enunciados pueden pedir **primer bit en destino**, **último bit en destino** o **último ACK recibido**: no son el mismo instante.

## 4. Bits que hay «en vuelo»

```text
Bits_en_vuelo = V · t_p
```

También llamado producto **ancho de banda × retardo de propagación** (Bandwidth-Delay Product del enlace).

**Ejemplo:** `2 Mb/s · 0,08 s = 160.000 bits`.

## 5. Longitud física de un bit

```text
Longitud_de_un_bit = s / V     [metros/bit]
```

**Ejemplo:** `2,5·10^8 / 2·10^6 = 125 m por bit`.

Si piden que **la longitud física de un bit sea igual a la distancia D**:

```text
V = s / D
```

## 6. ¿Dónde está el primer bit cuando termina de emitirse el paquete?

Compara `t_t` con `t_p`:

- Si `t_t < t_p`, el primer bit **todavía está en el enlace**; ha recorrido `s·t_t` metros.
- Si `t_t = t_p`, el primer bit **acaba de llegar**.
- Si `t_t > t_p`, el primer bit **ya llegó** al destino.

## 7. Tamaño máximo/mínimo para ocupar un canal sin pausas

Si transmites una foto por minuto y quieres mantener ocupado un enlace durante esos 60 s:

```text
Tamaño de foto (bits) = V · 60 s
```

**Ejemplo:** a 10 Mbps → `600.000.000 bits = 75.000.000 bytes = 75 MB` (unidades decimales).

En el problema del satélite, para el retardo usa `t_p = distancia estación-satélite / s`. Si solo se da la altitud y no la posición de la estación, hay que **declarar** la suposición de satélite en la vertical.

## 8. Fragmentar un mensaje en varios paquetes (introducción)

En **un solo enlace**, sin cabeceras ni esperas, dividir los datos no cambia su tiempo total de transmisión:

```text
T_transmisión_total = M / V
```

La división gana importancia con **varios enlaces**, por el *pipelining* (varios paquetes simultáneamente en distintos enlaces), la MTU y las retransmisiones parciales. Demasiados paquetes añaden cabeceras y procesamiento.

## 9. Preguntas teóricas del Tema 1: esquema mínimo

- **Elementos de un sistema de comunicación:** fuente → transmisor → canal → receptor → destino.
- **Cliente-servidor:** cliente solicita recursos a un servidor; **P2P:** los equipos pueden compartir recursos directamente como pares.
- **ADSL:** tecnología de acceso sobre par de cobre, normalmente asimétrica (bajada superior a subida).
- **OSI:** 7 capas: física, enlace, red, transporte, sesión, presentación, aplicación.
- **TCP/IP del temario:** red subyacente, red, transporte, aplicación.
- **Comunicación real:** vertical, entre capas adyacentes del mismo equipo. **Virtual:** horizontal, entre entidades homólogas.
- **Encapsulación:** `PDU = cabecera + SDU` (en su forma básica).
- **Ejercicios de traductores:** identifica el idioma/protocolo común de cada salto; dibuja los intermediarios y las capas; diferencia comunicación local real y comunicación horizontal virtual.

---

# TEMA 2 — Capa de red

## 10. Conmutación de paquetes con Store & Forward

Datos típicos: mensaje `M`, velocidad `V`, paquete `P`, cabecera `H`, `N` routers intermedios, procesamiento `D` por router, propagación `R` por enlace.

**Paso 1. Datos útiles de cada paquete**

```text
Datos_útiles = P - H
```

**Paso 2. Número de paquetes**

```text
n = ceil( M / (P-H) )
```

**Paso 3. Transmisión por paquete**

```text
t_t = P / V
```

**Paso 4. Primer paquete completo en destino**

Si hay `N` routers, existen `N+1` enlaces:

```text
T_primero = (N+1)·(t_t + R) + N·D
```

**Paso 5. Todos los paquetes, si son iguales y el pipeline mantiene ritmo `t_t`**

```text
T_total = T_primero + (n-1)·t_t
```

**Condiciones de esta última fórmula:** enlaces con la misma velocidad, paquetes de igual tamaño, sin cuellos de botella adicionales ni colas que modifiquen el ritmo. Si el último paquete es más pequeño o las velocidades difieren, ajusta el cronograma.

**Ejemplo del ejercicio 1 del T2:** `M = 240.000 b`, `P=8.200 b`, `H=200 b`, `V=4 Mbps`, `N=2`, `R=3 ms`, `D=0,5 ms`: `n=30`; `t_t=2,05 ms`; `T_primero=16,15 ms`; `T_total=75,60 ms`.

## 11. Conmutación de circuitos

Una vez establecido el circuito, los bits fluyen de extremo a extremo:

```text
T_circuitos = T_establecimiento + M/V + Σ(t_prop de cada enlace)
```

No multipliques `M/V` por cada router como si fuera Store & Forward. **Compara con paquetes** teniendo en cuenta el tiempo de establecimiento, las cabeceras y el pipelining.

## 12. IPv4: máscara, red, broadcast, hosts

Para una IP `/p`:

```text
Bits_host          = 32-p
Direcciones_totales = 2^(32-p)
Hosts_utilizables   = 2^(32-p)-2    [subred IPv4 convencional]
Red                 = IP AND Máscara
```

En una subred convencional:

```text
Broadcast = última dirección del bloque
Primer host = Red + 1
Último host = Broadcast - 1
```

**Método rápido:** convierte la máscara del octeto relevante y calcula `salto = 256 - octeto_máscara`. Localiza entre qué múltiplos del salto cae la IP.

| Prefijo | Máscara | Direcciones totales | Hosts convencionales |
|---|---|---:|---:|
| /24 | 255.255.255.0 | 256 | 254 |
| /25 | 255.255.255.128 | 128 | 126 |
| /26 | 255.255.255.192 | 64 | 62 |
| /27 | 255.255.255.224 | 32 | 30 |
| /28 | 255.255.255.240 | 16 | 14 |
| /29 | 255.255.255.248 | 8 | 6 |
| /30 | 255.255.255.252 | 4 | 2 |

**Ejemplo:** `192.168.10.141/27`: salto 32 → bloque `.128–.159`; red `.128`; broadcast `.159`; hosts `.129–.158` (30).

**Excepción:** `/31` se puede utilizar en ciertos enlaces punto a punto si lo soportan ambos extremos; la fórmula `-2` no se aplica igual en ese caso.

## 13. VLSM: repartir un bloque en subredes de diferente tamaño

**Procedimiento obligatorio:**

1. Ordena las LAN **de mayor a menor** número de hosts.
2. Para cada LAN, busca el mínimo `h` que cumpla `2^h-2 ≥ hosts necesarios`.
3. Obtén su prefijo `p = 32-h` y tamaño de bloque `2^h`.
4. Asigna el siguiente bloque libre **alineado** con ese tamaño; no solapes subredes.
5. Calcula red, broadcast y primera/última IP de cada bloque.
6. Cuenta direcciones libres **fuera** de los bloques asignados (no confundir con hosts sin usar dentro de una LAN).

**Ejemplo:** bloque `192.168.50.0/24` para 90, 50, 25 y 2 hosts → `/25`, `/26`, `/27`, `/30`, con redes `.0`, `.128`, `.192`, `.224`. Quedan 28 direcciones del bloque original sin asignar a esas subredes.

**Importante:** `.0` y `.255` no se reservan *además* para el `/24` padre cuando ya se está dividiendo en subredes. Cada subred tiene sus propios identificadores de red y broadcast.

## 14. Direcciones IP de las interfaces entre routers

- Cada **interfaz IP** de un router pertenece a una subred concreta.
- Enlace punto a punto R1–R2: es habitual asignar **una subred `/30`** con dos IP utilizables.
- Triángulo R1–R2, R1–R3, R2–R3 con enlaces independientes → **3 subredes `/30`**, una por enlace.
- Si varios routers comparten **un switch y una misma VLAN**, pueden compartir una única subred para ese segmento, en vez de crear una `/30` por pareja.

## 15. Elegir una ruta: coincidencia de prefijo más largo

Para cada fila de una tabla:

```text
IP_destino AND Máscara_fila = Red_destino_de_la_fila ?
```

**Procedimiento:**

1. Extrae la IP de destino del paquete.
2. Comprueba las coincidencias realizando el AND con cada máscara.
3. De las rutas que coincidan, elige la de **prefijo más largo** (`/24` antes que `/16`, `/8`, `/0`).
4. Si es ruta directa, entrega en esa subred; si es remota, usa el siguiente salto.
5. Si solo coincide `0.0.0.0/0`, usa la ruta por defecto; si no hay ruta aplicable, no se puede reenviar.

**Ejemplo:** `10.18.0.3` coincide con `10.0.0.0/8` y con la ruta `/0`, pero **no** con `10.20.0.0/16`. Gana `/8`.

## 16. Construir tablas de encaminamiento desde la topología

1. **Dibuja todas las subredes**: LAN y redes de enlace entre routers.
2. **Asigna IP a las interfaces** de cada router dentro de su subred.
3. Para **cada router**, añade primero sus redes directamente conectadas (sin siguiente router).
4. Añade las **redes remotas** con el siguiente salto: una IP del **router vecino** que sea alcanzable directamente.
5. Añade ruta por defecto `0.0.0.0/0` hacia la salida adecuada cuando se necesite.
6. Prueba un paquete de ida **y otro de vuelta**: cada router debe saber cómo alcanzar ambos destinos.
7. Minimiza entradas solo cuando la agregación respete la topología y el siguiente salto.

**Nunca** pongas como siguiente salto la IP de la propia interfaz. Ejercicio R2: para llegar a LAN A `192.168.0.0/27`, R2 reenvía hacia R1 `192.168.0.33`.

## 17. Agregación de rutas

Ejemplo:

```text
192.168.2.0/24 + 192.168.3.0/24 = 192.168.2.0/23
```

Condiciones: bloques contiguos, alineados con el nuevo prefijo **y** compatibles con los siguientes saltos. No agregues por comodidad si las redes deben salir por routers distintos.

## 18. RIP y OSPF

- **RIP:** métrica = **número de saltos**. Elige el camino con menos saltos.
- **OSPF:** métrica = **suma de costes** de los enlaces; elige el coste total menor. En los ejercicios, usa **la fórmula de coste que se indique** (por ejemplo, `100 / velocidad_en_Mbps`).

**Procedimiento:** enumera rutas candidatas, suma saltos para RIP, suma costes de enlaces para OSPF, compara. Ruta directa de 1 Mbps = 1 salto pero coste 100; ruta de 2 saltos a 20 Mbps cada uno = coste 5+5=10.

## 19. Fragmentación IPv4

Si un datagrama de longitud `L` excede la MTU y `DF=0`:

```text
Datos_originales       = L - H
Datos_máximos_fragmento = 8 · floor((MTU-H)/8)
Nº fragmentos          = ceil(Datos_originales / Datos_máximos_fragmento)
Longitud_fragmento_i   = Datos_i + H
Offset_campo_i         = Datos anteriores al fragmento / 8
MF                     = 1 si quedan fragmentos; 0 en el último
Total_IP_transmitido   = Datos_originales + Nº_fragmentos·H
```

**Procedimiento:** separa cabecera y datos originales; calcula carga máxima múltiplo de 8; reparte datos (último puede ser más corto); suma cabecera a cada fragmento; calcula offsets usando **solo los datos anteriores**; marca `MF=1, ..., 0`.

**Ejemplo del ejercicio:** L=4200 B, H=20 B, MTU=1500 B → datos 4180 B; tres cargas `1480,1480,1220` B; longitudes `1500,1500,1240` B; **campo Offset** `0,185,370`; **MF** `1,1,0`; total 4240 B.

**Ojo:** el esquema de la diapositiva 42 del Tema 2 muestra posiciones 0, 1480 y 2960 **en bytes**; en el **campo IPv4** se almacenan en unidades de 8 bytes: 0, 185, 370.

## 20. NAT/PAT

- Al salir: `IP_privada:puerto_interno` → `IP_pública:puerto_externo`.
- El router guarda la traducción; cuando llega la respuesta al puerto externo, recupera el dispositivo interno.
- Dos hosts pueden usar el **mismo puerto interno** porque sus IP privadas difieren; el NAT los distingue por el mapeo.

## 21. ARP, switch, DHCP e ICMP

**ARP:** si el destino está en la misma subred, resolver la MAC del **destinatario**. Si está fuera, resolver la MAC de la **puerta de enlace** (si hay ruta). ARP Request suele ir en broadcast; Reply, normalmente unicast.

**Switch:** aprende **MAC de origen + puerto + VLAN**. Si desconoce la MAC destino, hace *flooding* dentro de la VLAN (excepto por el puerto de entrada).

**DHCP:** secuencia DORA = Discover → Offer → Request → ACK. Servidor **UDP 67** y cliente **UDP 68**. Concesión: intenta renovar antes de caducar; si caduca sin renovación, deja de usar la IP asignada.

**ICMP:** `ping` utiliza Echo Request (**tipo 8**) y Echo Reply (**tipo 0**) en IPv4; TTL agotado durante el tránsito puede provocar Time Exceeded (**tipo 11, código 0**).

---

# TEMA 3 — Capa de transporte: ejercicios TCP del seminario

## 22. Transmisión, RTT y propagación en TCP

Se reutilizan fórmulas del Tema 1:

```text
t_t = Tamaño_segmento_bits / Velocidad_bits_s
```

**Bajo los supuestos del seminario** (camino simétrico, ACK casi instantáneo, colas y procesamiento ≈ 0):

```text
RTT = t_t + 2·t_p

t_p = (RTT - t_t)/2
```

**Conexión TCP:** SYN, SYN+ACK, ACK. El seminario, al despreciar la transmisión de esos mensajes, computa `T_inicio = 3·t_p`; para el cierre simplificado FIN, FIN+ACK, ACK usa `T_cierre = 3·t_p`. **No es una duración universal de TCP**; aplicar si se pide explícitamente o la solución la incluye.

## 23. SEQ y ACK: números de secuencia

**TCP numera bytes, no segmentos.**

```text
SEQ_siguiente = SEQ_actual + bytes_de_datos_del_segmento
ACK = primer byte que todavía se espera recibir en orden
```

**Ejemplo:** datos comienzan en SEQ=5000 y MSS=1024 B:

```text
S1 SEQ=5000  → S2 SEQ=6024 → S3 SEQ=7048 → ...
Tras S1 y S2 recibidos en orden: ACK=7048.
```

Los ACK TCP son **acumulativos**. El SYN y el FIN consumen cada uno un número de secuencia; si el SYN inicial usa ISN=5000, el primer byte de datos empieza normalmente en 5001.

## 24. Inicio lento: crecimiento de CWIN

**Si no hay pérdidas ni limitación de RWIN**, en la idealización de ACK por cada segmento:

```text
CWIN_nueva = CWIN_vieja + (nuevos segmentos confirmados)·1 MSS
CWIN tras k rondas completas ≈ CWIN_inicial·2^k
```

Desde 1 MSS: `1 → 2 → 4 → 8 → 16 → 32 → 64` después de seis rondas.

**Segmentos enviados durante seis rondas desde CW=1:**

```text
1 + 2 + 4 + 8 + 16 + 32 = 2^6 - 1 = 63 segmentos
```

## 25. Prevención de congestión

Cuando CWIN ha alcanzado el umbral, en el modelo simplificado del curso crece aproximadamente **1 MSS por RTT**:

```text
T = (CWIN_final - CWIN_inicial) · RTT
```

**Ejemplo:** de 101 a 107 MSS → `6 RTT`.

**Con congestión y timeout, Tahoe (apuntes):** reducir el umbral aproximadamente a la mitad de CWIN y reiniciar CWIN a 1 MSS. No confundas comportamiento del algoritmo con los datos hipotéticos de cada ejercicio.

## 26. ACK retardado de 500 ms: cuándo usarlo

**Solo si el ejercicio adopta esa regla del seminario:**

- Si llega **un segmento aislado** y no llega otro a tiempo, el receptor puede retrasar su ACK hasta 500 ms en **este modelo docente**.
- Si llega un segundo segmento ordenado mientras había un ACK pendiente, puede emitir **un ACK acumulativo inmediato**.
- No sumes 500 ms automáticamente por cada ACK; dibuja qué datos llegan antes de que venza el temporizador.

**Seminario, problema 2:** desde CWIN=1 a ≥7 MSS se requieren 3 rondas más la espera inicial: `T = 3·RTT + 500 ms`.

## 27. Control de flujo: CWIN frente a RWIN

- **CWIN:** protege la **red**; la gestiona el **emisor** según señales de congestión.
- **RWIN:** protege al **receptor**; la anuncia **B** según espacio en búfer.

En un esquema simplificado:

```text
Límite de datos pendientes de ACK = min(CWIN, RWIN)
Ventana útil de recepción = RWIN anunciada - datos todavía en tránsito
```

Procedimiento (seminario, ejercicio 3):

1. Enviar cuantos segmentos permita la ventana inicial.
2. Al llegar cada ACK, actualizar **CWIN** según los segmentos nuevos confirmados.
3. Leer la **RWIN ofertada** en ese ACK y restar los datos todavía sin confirmar.
4. Si la ventana útil = 0, A se detiene aunque CWIN haya aumentado.
5. Deja consumir datos a la aplicación receptora y espera un ACK/anuncio con nueva ventana.
6. Reanuda el envío y termina cuando llega la confirmación final, **si eso es lo que pide el cronograma**.

**Ejemplo del problema 3:** CWIN=3, buffer=4 MSS, consumo=1 MSS cada 30 ms, RTT=10 ms (`t_p=5 ms`). A envía `S1–S3`, luego `S4–S5`, se bloquea, después envía `S6–S8`. Con dos esperas de 500 ms y confirmación final: `T = 3t_p + 500 + 2t_p + 500 + t_p = 1030 ms`.

## 28. Throughput medio

```text
Throughput_medio = Datos_totales_transmitidos / Tiempo_total
```

Usa la unidad coherente con el numerador: bits/s, bytes/s, MSS/s o MSS/ms.

**Problema 2:** después de seis rondas desde CW=1 se han enviado 63 segmentos; con la espera inicial del modelo:

```text
Throughput = 63 / (6·RTT + 500 ms)     [MSS/ms, si RTT está en ms]
```

No confundas la **CWIN final (64 MSS)** con el **número de segmentos ya enviados (63 MSS)**.

## 29. Transmisión continua TCP (problemas con muchos segmentos)

**Cuándo:** el enunciado contiene muchos segmentos (ej. 50) y no interesa dibujar uno a uno.

**Condición utilizada en el seminario** para sus ACK acumulativos, sin limitación de RWIN:

```text
CWIN · t_t >= RTT + t_t
CWIN >= 1 + RTT/t_t = 2 + 2·t_p/t_t
```

**Procedimiento:**

1. Calcula `t_t` y `RTT`.
2. Halla la **CWIN mínima para transmisión continua**.
3. Representa las tandas/ACK iniciales hasta alcanzar esa ventana.
4. Resta del total los segmentos ya transmitidos.
5. Tiempo para los restantes, en la fase continua: `N_restantes·t_t + 2·t_p` **si mides hasta que vuelve el último ACK**, como en el seminario.
6. Suma establecimiento y cierre si están incluidos por el enunciado/criterio del profesor.

**Ejemplo del examen de febrero 2025:** `t_t=10 ms`, `t_p=20 ms`, `RTT=50 ms`, CWIN inicial=2 MSS, umbral=8 MSS, 50 segmentos → transmisión continua al llegar a `CWIN≥6`.

```text
1ª tanda (S1–S2)        = 60 ms
2ª tanda (S3–S6)        = 60 ms
Resto (44 segmentos)    = 44·10 + 2·20 = 480 ms
Establecimiento         = 3·20 = 60 ms
Cierre simplificado     = 3·20 = 60 ms
TOTAL                   = 720 ms
```

El umbral 8 MSS indica transición de inicio lento a prevención de congestión, pero no obliga a detener una emisión ya continua.

## 30. Estimación de timeout (fórmulas de las diapositivas, aunque no aparecieron en el seminario)

Estas fórmulas están en el **Tema 3, diapositiva 25**:

```text
RTT_est_nuevo = (1-α)·RTT_est_viejo + α·RTT_medido
Desv_nueva    = (1-β)·Desv_vieja + β·|RTT_medido-RTT_est_nuevo|
Timeout       = RTT_est_nuevo + 4·Desv_nueva
```

**Karn:** no uses una confirmación ambigua de un segmento retransmitido para medir RTT; el temario propone duplicar el timeout ante retransmisión (`Timeout_nuevo = 2·Timeout_viejo`). Si el examen proporciona valores de α, β y muestras RTT, sustituye en este orden.

---

# 31. Selector rápido: ¿qué fórmula necesito?

| Si el enunciado dice… | Piensa en… |
|---|---|
| «Longitud de bits y velocidad» | `t_t=L/V` |
| «Distancia y velocidad de propagación» | `t_p=D/s` |
| «Bits en el enlace» | `V·t_p` |
| «Dos routers intermedios y paquetes» | `N+1` enlaces + Store & Forward + pipelining |
| «Conmutación de circuitos» | Establecimiento + mensaje/velocidad + propagación |
| «IP con /27» | Máscara, salto, red, broadcast, hosts |
| «Divide una /24 para LAN de tamaños distintos» | VLSM de mayor a menor |
| «¿Por qué interfaz saldrá el paquete?» | AND y prefijo más largo |
| «Diseña tablas de R1–R5» | Redes directas → siguientes saltos → default → retorno |
| «RIP vs OSPF» | Saltos vs suma de costes |
| «Datagrama > MTU» | Cargas múltiples de 8, Offset/8, MF |
| «IP privada sale a Internet» | NAT/PAT y tabla de puertos |
| «MAC del destino» | ARP al host local o a la puerta de enlace |
| «TCP, SEQ y ACK» | Bytes enviados y siguiente byte esperado |
| «CWIN inicial, RTT, no hay pérdidas» | Inicio lento / prevención |
| «Búfer receptor 4 MSS» | RWIN y ventana útil |
| «Throughput tras 6 rondas» | Segmentos realmente enviados / tiempo |
| «50 segmentos TCP» | Condición de transmisión continua |

# 32. Errores que más puntos pueden costar

1. **Bytes ≠ bits**. Convertir antes de usar Mbps.
2. **Transmisión ≠ propagación**. Dependen de variables diferentes.
3. **N routers ≠ N enlaces**. Con N routers intermedios hay N+1 enlaces.
4. **Subred/host/broadcast**. La máscara manda; no reservar `.0/.255` globalmente tras dividir con VLSM.
5. **Siguiente salto ≠ destino final**. Es la IP del vecino alcanzable directamente.
6. **Fragment Offset** en IPv4 está en **bloques de 8 bytes** y no incluye cabeceras previas.
7. **SEQ/ACK** se miden en **bytes**, no por número de segmento.
8. **CWIN no es RWIN**. La red puede permitir transmitir pero el receptor impedirlo.
9. **500 ms de ACK retardado**: regla del seminario, no añadir automáticamente en cualquier problema.
10. **¿Qué instante mide el problema?** Último bit emitido, último dato recibido, último ACK recibido o conexión cerrada dan tiempos diferentes.

---

## Fuentes y alcance

- `Tema1_2627.pdf`: modelos y terminología (pp. 16–22); problemas de retardos y conceptos trabajados en nuestras soluciones del Tema 1.
- `Tema2_202627.pdf`: conmutación (pp. 8–12), IPv4 y máscaras (pp. 16–19), tablas (pp. 29–34), RIP/OSPF (pp. 37–39), fragmentación (pp. 40–42), ARP/ICMP/DHCP.
- `Tema3.pdf`: TCP/ACK (pp. 21–23), estimación RTT (p. 25), ventanas y flujo (pp. 27–29), congestión (pp. 31–33).
- `Seminario4_ProblemasT3.pdf`, titulado internamente «Seminario 5»: soluciones de los problemas 1–5 (pp. 2–31), **cuyas hipótesis temporales particulares se han conservado y señalado**.
- Las cifras de ejemplo del Tema 2 proceden de los ejercicios adicionales que resolvimos en esta conversación; cuando difieren de las cifras del PDF del temario, se identifican como **ejemplos trabajados**, no como citas literales del temario.
