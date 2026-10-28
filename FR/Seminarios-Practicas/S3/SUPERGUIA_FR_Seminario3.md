# SUPERGUÍA — FUNDAMENTOS DE REDES · SEMINARIO 3

**Guion principal:** Problemas 1, 2 y 3. **Guion autónomo:** Problema 1.

Esta guía reúne el procedimiento, los cálculos y los resultados que trabajamos. Las soluciones del PDF principal son las del profesor; las direcciones y las tablas del autónomo son **nuestra propuesta** para su topología.

---

# 0. Conceptos que se usan en varios problemas

## IP, máscara, subred y gateway

- **Dirección IPv4:** identifica una interfaz de red (por ejemplo, `192.168.0.10`). Tiene 32 bits.
- **Subred:** conjunto de direcciones que comparten un prefijo. `192.168.0.0/25` es una subred.
- **Prefijo `/25`:** 25 bits de red y 7 de host. Máscara: `255.255.255.128`.
- **Dirección de red:** primera dirección de la subred; no se asigna a un PC en estas subredes convencionales.
- **Broadcast:** última dirección de la subred; tampoco se asigna a un PC.
- **Gateway:** dirección de la interfaz del router de esa LAN; permite salir a **otras** redes.
- **Router:** cada interfaz pertenece a la subred a la que está conectada. Un router puede tener varias IP.

Ejemplo, `192.168.0.0/25`:

| Concepto       | Valor                            |
| -------------- | -------------------------------- |
| Red            | `192.168.0.0`                    |
| Máscara        | `255.255.255.128`                |
| IP utilizables | `192.168.0.1` – `192.168.0.126`  |
| Broadcast      | `192.168.0.127`                  |
| Capacidad      | 128 direcciones, 126 utilizables |

Un ordenador que quiere comunicarse con otro de **su misma subred** no necesita enviar el tráfico a un router; si está en otra, normalmente utiliza su gateway.

## Cálculo del prefijo con VLSM

1. Contar los equipos que necesitan IP: hosts **más las interfaces de router** de esa subred.
2. Buscar el menor número `h` que satisfaga `2^h - 2 >= H`.
3. Calcular el prefijo: `32 - h`.
4. Reservar un bloque de `2^h` direcciones, sin solaparse con otro.

| Prefijo | Máscara         | Direcciones totales | Utilizables |
| ------- | --------------- | ------------------- | ----------- |
| /30     | 255.255.255.252 | 4                   | 2           |
| /29     | 255.255.255.248 | 8                   | 6           |
| /27     | 255.255.255.224 | 32                  | 30          |
| /26     | 255.255.255.192 | 64                  | 62          |
| /25     | 255.255.255.128 | 128                 | 126         |
| /24     | 255.255.255.0   | 256                 | 254         |
| /23     | 255.255.254.0   | 512                 | 510         |
| /22     | 255.255.252.0   | 1024                | 1022        |

**Nota:** el cálculo `-2` corresponde a subredes IPv4 convencionales; los enlaces punto a punto `/31` tienen reglas específicas, pero no los utilizamos en estas soluciones.

## IP privada, pública y PAT

Los rangos privados establecidos en RFC 1918 incluyen `10.0.0.0/8`, `172.16.0.0/12` y `192.168.0.0/16`. **No es obligatorio escoger `192.168.0.0/16`**: fue una elección de diseño, utilizada también en la solución del profesor del Problema 1.

- **IP pública:** dirección enrutable en Internet; en un ejercicio debe pertenecer al bloque disponible.
- **IP privada:** no se enruta directamente por Internet pública.
- **PAT (NAT con sobrecarga):** varios equipos privados comparten una sola IP pública, diferenciando las conexiones mediante puertos.

## Cómo leer una tabla de encaminamiento

| Campo             | Qué indica                                                     |
| ----------------- | -------------------------------------------------------------- |
| Destino y máscara | Subred a la que se aplica la ruta                              |
| Siguiente salto   | IP del router vecino al que entregar el paquete                |
| Interfaz          | Puerto o conexión por donde sale                               |
| Directa           | Red conectada al propio router: no necesita un salto adicional |
| `0.0.0.0/0`       | Ruta por defecto para los destinos sin ruta más específica     |

**Regla fundamental:** si coinciden varias rutas, se utiliza la de **prefijo más largo**. Por ejemplo, para un destino que coincide con `/22`, `/24` y `/29`, gana `/29`.

**Agregación:** dos o más redes pueden resumirse con una ruta si forman un bloque CIDR alineado y están detrás del **mismo siguiente salto**. No basta con que sean consecutivas.

---

# PDF 1 — PROBLEMA 1. Direccionamiento y encaminamiento

## Enunciado y decisión de diseño

- Departamento A: 90 ordenadores; departamento B: 90.
- Red WiFi: el solucionario **supone 200 visitantes**, no es una cantidad fijada en el enunciado.
- Routers: RA (Internet), RB (interconexión), RC (A), RD (B), RE (WiFi).
- Extranet: servidores WEB y MAIL.
- El ISP proporciona **una sola IP pública:** `44.44.44.44`.

No hay direcciones públicas suficientes para numerar individualmente todos los equipos. Se asignan IP privadas a la empresa y RA utiliza **PAT** para la salida a Internet.

## 1. Calcular las máscaras

| Subred             | Dispositivos que necesitan IP | Cálculo         | Resultado |
| ------------------ | ----------------------------- | --------------- | --------- |
| A + RC             | 90 + 1 = 91                   | `2^7 - 2 = 126` | `/25`     |
| B + RD             | 90 + 1 = 91                   | `2^7 - 2 = 126` | `/25`     |
| Visitantes + RE    | 200 + 1 = 201                 | `2^8 - 2 = 254` | `/24`     |
| RB, RC, RD y RE    | 4 interfaces                  | `2^3 - 2 = 6`   | `/29`     |
| RB, RA, WEB y MAIL | 4 interfaces/equipos          | `2^3 - 2 = 6`   | `/29`     |

Ejemplo para A: 6 bits dan solo 62 IP utilizables; con 7 obtenemos 126. `32 - 7 = 25` → `/25`.

## 2. Asignación del profesor

| Red           | Subred           | Máscara         | Rango utilizable       | Broadcast       |
| ------------- | ---------------- | --------------- | ---------------------- | --------------- |
| A             | `192.168.0.0/25` | 255.255.255.128 | `192.168.0.1` – `.126` | `192.168.0.127` |
| B             | `192.168.1.0/25` | 255.255.255.128 | `192.168.1.1` – `.126` | `192.168.1.127` |
| WiFi          | `192.168.2.0/24` | 255.255.255.0   | `192.168.2.1` – `.254` | `192.168.2.255` |
| Interconexión | `192.168.3.0/29` | 255.255.255.248 | `192.168.3.1` – `.6`   | `192.168.3.7`   |
| Extranet      | `192.168.4.0/29` | 255.255.255.248 | `192.168.4.1` – `.6`   | `192.168.4.7`   |

El profesor deja huecos sin asignar (por ejemplo, `192.168.0.128`–`.255`) por simplicidad y para poder ampliar. **No es necesario compactar al máximo el uso de IP privadas**.

## 3. IP de interfaces y servidores

| Dispositivo | Conexión      | IP                                                  |
| ----------- | ------------- | --------------------------------------------------- |
| RC          | Red A         | `192.168.0.1/25`                                    |
| RC          | Interconexión | `192.168.3.2/29`                                    |
| RD          | Red B         | `192.168.1.1/25`                                    |
| RD          | Interconexión | `192.168.3.3/29`                                    |
| RE          | WiFi          | `192.168.2.1/24`                                    |
| RE          | Interconexión | `192.168.3.4/29`                                    |
| RB          | Interconexión | `192.168.3.1/29`                                    |
| RB          | Extranet      | `192.168.4.1/29`                                    |
| RA          | Extranet      | `192.168.4.2/29`                                    |
| RA          | Internet      | `44.44.44.44` (máscara WAN no dada en el enunciado) |
| WEB         | Extranet      | `192.168.4.3/29`                                    |
| MAIL        | Extranet      | `192.168.4.4/29`                                    |

## 4. Rutas necesarias (resumen)

- **RC, RD y RE:** conocen sus LAN e interconexión directamente y tienen `0.0.0.0/0` hacia **RB**, siguiente salto `192.168.3.1`.
- **RB:** conoce directamente `192.168.3.0/29` y `192.168.4.0/29`. Para A usa `192.168.3.2`; para B, `192.168.3.3`; para WiFi, `192.168.3.4`. Su ruta por defecto apunta a `192.168.4.2` (RA).
- **RA:** conoce directamente Extranet y su enlace público; hacia las redes internas puede resumir `192.168.0.0/22` con siguiente salto `192.168.4.1` (RB). Ruta por defecto hacia el ISP.
- **Servidores:** red `192.168.4.0/29` directa; ruta `192.168.0.0/22` hacia RB (`192.168.4.1`) y ruta por defecto hacia RA (`192.168.4.2`).

**Detalles del solucionario a vigilar:**

1. La tabla de RB escribe `192.168.1.0/24`, aunque al departamento B se le asignó `/25`. Para reflejar exactamente la subred, usar `/25`.
2. En la tabla de los servidores aparece `eth1` para la ruta interna, aunque la única conexión dibujada es hacia Extranet. Para una interfaz única, sería esa misma interfaz (`eth0`); el dato de la diapositiva es inconsistente.
3. La tabla de RA supone la red `44.44.44.0/24`, pero el enunciado solo garantiza la IP `44.44.44.44`, sin máscara ni puerta de enlace del ISP.

---

# PDF 1 — PROBLEMA 2. Elegir rutas con RIP y OSPF

Queremos llegar del host **A** al host **B** atravesando R1–R7. El dibujo muestra **ancho de banda (Mbps)** y **retardo (ms)** de cada enlace.

## 1. RIP: elegir menos saltos

RIP es de **vector-distancia** y utiliza el número de saltos como métrica. En el esquema, hay varios caminos de tres enlaces entre routers:

| Recorrido              | Saltos |
| ---------------------- | ------ |
| R1 → R2 → R4 → R5      | 3      |
| R1 → R6 → R4 → R5      | 3      |
| R1 → R6 → R7 → R5      | 3      |
| R1 → R2 → R3 → R4 → R5 | 4      |

**Conclusión RIP:** las tres primeras rutas tienen coste mínimo, **3 saltos**. El profesor ilustra `A → R1 → R2 → R4 → R5 → B`. Su solución explica que puede quedar elegida la primera ruta aprendida; en implementaciones reales también puede haber balanceo entre rutas de igual coste, según configuración.

Los Mbps y los milisegundos **no intervienen directamente** en esta decisión RIP.

## 2. OSPF: elegir menor coste

OSPF es de **estado de enlace**. En **el modelo simplificado del profesor**, el coste es la suma de las inversas de los anchos de banda:

`Coste total = Σ (1 / BW del enlace)`

Un enlace más rápido produce menor coste. Los retardos (ms) de la figura **no se incluyen** en esa fórmula. En redes reales, OSPF utiliza costes de interfaz configurables; `1/BW` es aquí una simplificación apropiada para comparar caminos.

| Recorrido              | Cálculo                  | Coste    |
| ---------------------- | ------------------------ | -------- |
| R1 → R2 → R4 → R5      | `1/5 + 1/3 + 1/5`        | 0.7333   |
| **R1 → R6 → R4 → R5**  | **`1/4 + 1/10 + 1/5`**   | **0.55** |
| R1 → R6 → R7 → R5      | `1/4 + 1/4 + 1/4`        | 0.75     |
| R1 → R2 → R3 → R4 → R5 | `1/5 + 1/5 + 1/5 + 1/5`  | 0.80     |
| R1 → R6 → R7 → R4 → R5 | `1/4 + 1/4 + 1/25 + 1/5` | 0.74     |

**Conclusión OSPF:** `A → R1 → R6 → R4 → R5 → B`, porque `0.55` es el menor coste de los caminos del esquema. OSPF usa algoritmos como Dijkstra para hallar el camino mínimo.

**Diferencia esencial:** RIP minimiza **saltos**; el OSPF del ejercicio minimiza **coste por ancho de banda**. Más saltos no implica necesariamente peor coste OSPF.

---

# PDF 1 — PROBLEMA 3. Subredes públicas, NAT y fragmentación

## Apartado A. Repartir el bloque público

El ISP proporciona `150.150.150.0/24`, es decir, **256 direcciones totales**. La empresa tiene las redes A (100 hosts), B (50), C (25), tres servidores (WEB, FTP1 y MAIL) y conexiones entre routers.

Asignamos por VLSM de mayor a menor para no solapar bloques:

| Red/enlace            | IP necesarias | Subred               | IP utilizables  | Broadcast |
| --------------------- | ------------- | -------------------- | --------------- | --------- |
| A + RA                | 101           | `150.150.150.0/25`   | `.1` – `.126`   | `.127`    |
| B + RB                | 51            | `150.150.150.128/26` | `.129` – `.190` | `.191`    |
| C + RC                | 26            | `150.150.150.192/27` | `.193` – `.222` | `.223`    |
| RX + WEB + FTP + MAIL | 4             | `150.150.150.224/29` | `.225` – `.230` | `.231`    |
| RX–RA                 | 2             | `150.150.150.232/30` | `.233` – `.234` | `.235`    |
| RA–RB                 | 2             | `150.150.150.236/30` | `.237` – `.238` | `.239`    |
| RB–RC                 | 2             | `150.150.150.240/30` | `.241` – `.242` | `.243`    |

Las terminaciones `.x` pertenecen en esta tabla a `150.150.150.x`.

`128 + 64 + 32 + 8 + 4 + 4 + 4 = 244` direcciones reservadas. `256 - 244 = 12` direcciones sin asignar al final del bloque (`.244` a `.255`).

**La solución del profesor emplea `150.150.150.193` como IP del servidor FTP de la red C** para el apartado B. No confundirlo con `FTP1` de la red de servidores.

## Apartado B. Entender la fragmentación

Trabajamos con un **datagrama IPv4** de **1480 bytes de datos + 20 de cabecera IP = 1500 bytes**. A medida que atraviesa enlaces, la **MTU** determina el tamaño máximo permitido para un paquete IP.

- **ID:** identifica los fragmentos del datagrama. El profesor usa `ID = 1` como ejemplo.
- **Offset:** posición de los datos dentro del datagrama original, en unidades de **8 bytes**.
- **MF (More Fragments):** `1` si aún siguen otros fragmentos del datagrama; `0` para el último.
- **Cada fragmento tiene su propia cabecera IP de 20 bytes.** Los datos de un fragmento no final se ajustan a múltiplos de 8.
- **Los routers normalmente no reensamblan**: lo hace el destino.

### PETICIÓN: PC de casa → servidor FTP de la red C

**Recorrido:** PC → RY → Internet → RX → RA → RB → RC → servidor FTP.

- IP origen inicial: `192.168.1.33` (PC).
- Después de NAT en RY: origen `8.8.8.8` (IP pública de RY).
- IP destino siempre: `150.150.150.193` (FTP de red C).
- Puerto origen ilustrativo: `1037`; puerto destino: `21` (control FTP).
- `ID = 1` en la tabla didáctica.

**Tramo con MTU 1000, RX → RA**

`1000 - 20 = 980` bytes teóricos de datos; máximo múltiplo de 8: `floor(980/8) × 8 = 976`.

`1480 = 976 + 504`.

| Fragmento | Datos | Tamaño IP (datos + 20) | Offset | MF  |
| --------- | ----- | ---------------------- | ------ | --- |
| 1         | 976   | 996                    | 0      | 1   |
| 2         | 504   | 524                    | 122    | 0   |

El segundo empieza después de 976 bytes: `976/8 = 122`.

**Tramo con MTU 500, RA → RB**

`500 - 20 = 480` bytes de datos (ya es múltiplo de 8).

- El fragmento 1 se divide: `976 = 480 + 480 + 16`.
- El fragmento 2 se divide: `504 = 480 + 24`.

| Fragmento | Datos | Tamaño IP | Offset | MF  |
| --------- | ----- | --------- | ------ | --- |
| 1A        | 480   | 500       | 0      | 1   |
| 1B        | 480   | 500       | 60     | 1   |
| 1C        | 16    | 36        | 120    | 1   |
| 2A        | 480   | 500       | 122    | 1   |
| 2B        | 24    | 44        | 182    | 0   |

Cálculos de offset: `480/8=60`, `960/8=120`, `976/8=122`, `(976+480)/8=182`.

**Importante:** 1C tiene `MF=1` porque todavía faltan 2A y 2B. Los cinco fragmentos continúan por RB → RC → FTP y el destino reconstruye el datagrama.

### RESPUESTA: servidor FTP → PC de casa

**Recorrido:** servidor FTP → RC → RB → RA → RX → Internet → RY → PC.

- IP origen: `150.150.150.193`.
- IP destino inicial: `8.8.8.8`; RY realiza NAT inverso y pasa a `192.168.1.33` al entregar al PC.
- Puerto origen: `21`; puerto destino: `1037`.
- La respuesta es **otro datagrama**; el profesor también le asigna `ID = 1` como ejemplo, no por obligación.

La respuesta atraviesa primero enlaces de MTU 1500, sin fragmentarse. Al llegar a **RB → RA (MTU 500)**:

`1480 = 480 + 480 + 480 + 40`.

| Fragmento | Datos | Tamaño IP | Offset | MF  |
| --------- | ----- | --------- | ------ | --- |
| 1         | 480   | 500       | 0      | 1   |
| 2         | 480   | 500       | 60     | 1   |
| 3         | 480   | 500       | 120    | 1   |
| 4         | 40    | 60        | 180    | 0   |

**¿Por qué salen cinco en la petición y cuatro en la respuesta?** Porque la petición se divide primero con MTU 1000 y después sus fragmentos se subdividen con MTU 500. La respuesta encuentra primero el enlace de MTU 500 y se divide directamente en cuatro. No se reensamblan al pasar a enlaces más grandes.

**Matices de lectura de las tablas oficiales:**

- En la página 19 aparece `MTU 500` para `RY → PC`, aunque el esquema indica una MTU de **1500** en ese enlace; los fragmentos conservan tamaños menores porque ya llegaron fragmentados.
- La tabla repite puertos de transporte en todos los fragmentos para facilitar la lectura. **Solo el fragmento que contiene el comienzo de los datos IP lleva la cabecera TCP con esos puertos**; los fragmentos posteriores no la duplican.
- FTP puerto 21 es de control; una transferencia FTP real puede usar otra conexión para datos.

---

# PDF 2 — EJERCICIO AUTÓNOMO. Resolución guiada completa

**Este PDF solo trae el enunciado: lo siguiente es nuestra propuesta, no una solución oficial del profesor.**

## 1. Entender la topología y los requisitos

- Ocho redes finales: A y B (25 hosts cada una), C y D (50), E y F (100), G y H (200).
- Siete routers: R0, R1, R2, R3, R4, R5 y R6.
- R3 conecta A y B; R4 conecta C y D; ambos llegan a R1.
- R5 conecta E y F; R6 conecta G y H; ambos llegan a R2.
- R1 y R2 llegan a R0; R0 tiene el enlace de Internet.
- Todos los hosts deben utilizar **IP públicas**.
- Rango contratado: `168.168.168.0/22`.
- Enlace de R0 a Internet: subred independiente `33.33.33.0/30`.
- Hay que minimizar las entradas de las tablas de encaminamiento mediante **agregación de rutas**.

**No se utiliza NAT/PAT para los hosts:** sus direcciones corporativas ya son públicas. El ISP debe tener una ruta de regreso hacia el bloque de la empresa.

## 2. Comprobar capacidad y calcular máscaras

El `/22` tiene `2^(32-22) = 1024` direcciones, desde `168.168.168.0` hasta `168.168.171.255`.

| Redes | Hosts + interfaz router | Cálculo         | Prefijo | Direcciones totales |
| ----- | ----------------------- | --------------- | ------- | ------------------- |
| A y B | 26 por red              | `2^5 - 2 = 30`  | /27     | 32 cada una         |
| C y D | 51 por red              | `2^6 - 2 = 62`  | /26     | 64 cada una         |
| E y F | 101 por red             | `2^7 - 2 = 126` | /25     | 128 cada una        |
| G y H | 201 por red             | `2^8 - 2 = 254` | /24     | 256 cada una        |

`2×32 + 2×64 + 2×128 + 2×256 = 960` direcciones reservadas para redes finales.

`1024 - 960 = 64` quedan inicialmente para interconexiones de routers.

## 3. Asignar subredes para poder agrupar

Repartimos bloques consecutivos y alineados detrás de un mismo router:

| Red | Router | Subred               | IP utilizables             | Broadcast         |
| --- | ------ | -------------------- | -------------------------- | ----------------- |
| G   | R6     | `168.168.168.0/24`   | `168.168.168.1` – `.254`   | `168.168.168.255` |
| H   | R6     | `168.168.169.0/24`   | `168.168.169.1` – `.254`   | `168.168.169.255` |
| E   | R5     | `168.168.170.0/25`   | `168.168.170.1` – `.126`   | `168.168.170.127` |
| F   | R5     | `168.168.170.128/25` | `168.168.170.129` – `.254` | `168.168.170.255` |
| C   | R4     | `168.168.171.0/26`   | `168.168.171.1` – `.62`    | `168.168.171.63`  |
| D   | R4     | `168.168.171.64/26`  | `168.168.171.65` – `.126`  | `168.168.171.127` |
| A   | R3     | `168.168.171.128/27` | `168.168.171.129` – `.158` | `168.168.171.159` |
| B   | R3     | `168.168.171.160/27` | `168.168.171.161` – `.190` | `168.168.171.191` |

Las terminaciones abreviadas mantienen los tres primeros octetos de la IP de la fila.

**Agrupaciones posibles:**

| Redes | Ruta resumen         | Router al que apunta |
| ----- | -------------------- | -------------------- |
| A + B | `168.168.171.128/26` | R3                   |
| C + D | `168.168.171.0/25`   | R4                   |
| E + F | `168.168.170.0/24`   | R5                   |
| G + H | `168.168.168.0/23`   | R6                   |

Por ejemplo, dos `/24` consecutivas G (`.168`) y H (`.169`) ocupan 512 direcciones y pueden expresarse como un único `/23`. **Se hace para reducir rutas, no porque los hosts compartan una sola LAN.**

## 4. Asignar IP a gateways y enlaces

Primera IP utilizable para la interfaz de cada router hacia su LAN (elección nuestra):

| Router | Red | Gateway de la LAN    |
| ------ | --- | -------------------- |
| R3     | A   | `168.168.171.129/27` |
| R3     | B   | `168.168.171.161/27` |
| R4     | C   | `168.168.171.1/26`   |
| R4     | D   | `168.168.171.65/26`  |
| R5     | E   | `168.168.170.1/25`   |
| R5     | F   | `168.168.170.129/25` |
| R6     | G   | `168.168.168.1/24`   |
| R6     | H   | `168.168.169.1/24`   |

Un PC de A puede usar IP `168.168.171.130/27` y gateway `168.168.171.129`. Otro equipo de A podría usar `.131` con el mismo gateway.

Los seis enlaces entre routers necesitan **2 IP útiles** cada uno. Elegimos subredes `/30` (4 direcciones por enlace):

| Enlace | Subred               | IP de cada extremo   |
| ------ | -------------------- | -------------------- |
| R3–R1  | `168.168.171.192/30` | R3 `.193`, R1 `.194` |
| R4–R1  | `168.168.171.196/30` | R4 `.197`, R1 `.198` |
| R5–R2  | `168.168.171.200/30` | R5 `.201`, R2 `.202` |
| R6–R2  | `168.168.171.204/30` | R6 `.205`, R2 `.206` |
| R1–R0  | `168.168.171.208/30` | R1 `.209`, R0 `.210` |
| R2–R0  | `168.168.171.212/30` | R2 `.213`, R0 `.214` |

En esa tabla las IP abreviadas empiezan por `168.168.171`.

Reservamos `6×4 = 24` direcciones para enlaces. Quedan `64 - 24 = 40` direcciones libres (`168.168.171.216`–`.255`).

**Internet:** el enunciado proporciona `33.33.33.0/30`. Nuestra propuesta asigna `33.33.33.1` a R0 y `33.33.33.2` al ISP; `33.33.33.0` es red y `.3` es broadcast. Son IP adicionales, ajenas al bloque `/22`.

## 5. Tablas de encaminamiento (las de nuestra propuesta)

Las redes conectadas directamente aparecen siempre en la tabla. Las rutas por defecto se reservan para los destinos que no coinciden con una ruta más específica.

### R3 — A y B

| Destino           | Máscara | Siguiente salto   | Salida   |
| ----------------- | ------- | ----------------- | -------- |
| `168.168.171.128` | /27     | Directa           | Red A    |
| `168.168.171.160` | /27     | Directa           | Red B    |
| `168.168.171.192` | /30     | Directa           | Hacia R1 |
| `0.0.0.0`         | /0      | `168.168.171.194` | Hacia R1 |

### R4 — C y D

| Destino           | Máscara | Siguiente salto   | Salida   |
| ----------------- | ------- | ----------------- | -------- |
| `168.168.171.0`   | /26     | Directa           | Red C    |
| `168.168.171.64`  | /26     | Directa           | Red D    |
| `168.168.171.196` | /30     | Directa           | Hacia R1 |
| `0.0.0.0`         | /0      | `168.168.171.198` | Hacia R1 |

### R5 — E y F

| Destino           | Máscara | Siguiente salto   | Salida   |
| ----------------- | ------- | ----------------- | -------- |
| `168.168.170.0`   | /25     | Directa           | Red E    |
| `168.168.170.128` | /25     | Directa           | Red F    |
| `168.168.171.200` | /30     | Directa           | Hacia R2 |
| `0.0.0.0`         | /0      | `168.168.171.202` | Hacia R2 |

### R6 — G y H

| Destino           | Máscara | Siguiente salto   | Salida   |
| ----------------- | ------- | ----------------- | -------- |
| `168.168.168.0`   | /24     | Directa           | Red G    |
| `168.168.169.0`   | /24     | Directa           | Red H    |
| `168.168.171.204` | /30     | Directa           | Hacia R2 |
| `0.0.0.0`         | /0      | `168.168.171.206` | Hacia R2 |

### R1 — conecta R3, R4 y R0

| Destino           | Máscara | Siguiente salto   | Salida         |
| ----------------- | ------- | ----------------- | -------------- |
| `168.168.171.192` | /30     | Directa           | Hacia R3       |
| `168.168.171.196` | /30     | Directa           | Hacia R4       |
| `168.168.171.208` | /30     | Directa           | Hacia R0       |
| `168.168.171.128` | /26     | `168.168.171.193` | Hacia R3 (A+B) |
| `168.168.171.0`   | /25     | `168.168.171.197` | Hacia R4 (C+D) |
| `0.0.0.0`         | /0      | `168.168.171.210` | Hacia R0       |

### R2 — conecta R5, R6 y R0

| Destino           | Máscara | Siguiente salto   | Salida         |
| ----------------- | ------- | ----------------- | -------------- |
| `168.168.171.200` | /30     | Directa           | Hacia R5       |
| `168.168.171.204` | /30     | Directa           | Hacia R6       |
| `168.168.171.212` | /30     | Directa           | Hacia R0       |
| `168.168.170.0`   | /24     | `168.168.171.201` | Hacia R5 (E+F) |
| `168.168.168.0`   | /23     | `168.168.171.205` | Hacia R6 (G+H) |
| `0.0.0.0`         | /0      | `168.168.171.214` | Hacia R0       |

### R0 — centro y salida a Internet

| Destino           | Máscara | Siguiente salto   | Salida                           |
| ----------------- | ------- | ----------------- | -------------------------------- |
| `168.168.171.208` | /30     | Directa           | Hacia R1                         |
| `168.168.171.212` | /30     | Directa           | Hacia R2                         |
| `33.33.33.0`      | /30     | Directa           | Hacia ISP                        |
| `168.168.168.0`   | /22     | `168.168.171.213` | Hacia R2, regla general          |
| `168.168.171.0`   | /24     | `168.168.171.209` | Hacia R1, excepción              |
| `168.168.171.200` | /29     | `168.168.171.213` | Hacia R2, excepción para enlaces |
| `0.0.0.0`         | /0      | `33.33.33.2`      | Hacia ISP                        |

**¿Por qué se superponen en R0?**

- `168.168.168.10` → coincide con `/22`, va a **R2**.
- `168.168.171.130` → coincide con `/22` y `/24`: gana `/24`, va a **R1**.
- `168.168.171.201` → coincide con `/22`, `/24` y `/29`: gana `/29`, va a **R2**.
- `8.8.8.8` → no coincide con las corporativas: sale por la **ruta por defecto al ISP**.

Las rutas `/30` directamente conectadas de R0 también tienen prioridad sobre los resúmenes generales.

## 6. Recorrer un paquete (comprobación de la propuesta)

Ejemplo: PC de A `168.168.171.130/27` quiere llegar a PC de H `168.168.169.10/24`.

1. **PC A → R3:** el destino está fuera de A; entrega al gateway `168.168.171.129`.
2. **R3 → R1:** ruta por defecto, siguiente salto `168.168.171.194`.
3. **R1 → R0:** ruta por defecto, siguiente salto `168.168.171.210`.
4. **R0 → R2:** el destino coincide con el resumen `168.168.168.0/22`, vía `168.168.171.213`.
5. **R2 → R6:** coincide con `168.168.168.0/23`, vía `168.168.171.205`.
6. **R6 → PC H:** la red H `168.168.169.0/24` es directamente conectada.

La respuesta puede volver por `H → R6 → R2 → R0 → R1 → R3 → A` gracias a las rutas hacia A.

**Comprobación final del enunciado:** 8 redes con capacidad suficiente y direcciones públicas; 6 enlaces internos en `/30` más el enlace Internet separado; tablas de R0–R6; agregación de rutas para reducir entradas. No afirmamos un **mínimo matemático absoluto** de entradas, sino una propuesta compacta y coherente.

---

# Método de resolución según el tipo de pregunta

| Si te piden...                 | Haz esto                                                                                        |
| ------------------------------ | ----------------------------------------------------------------------------------------------- |
| **Asignar subredes**           | Cuenta hosts + interfaces → `2^h-2 >= H` → `/ (32-h)` → asigna bloques alineados sin solapar    |
| **Repartir un rango limitado** | Empieza por las subredes mayores; suma tamaños completos, no solo hosts utilizables             |
| **Minimizar tablas**           | Agrupa redes contiguas detrás del mismo router; usa rutas por defecto en ramas sin alternativas |
| **Construir una tabla**        | Anota redes directas, después rutas remotas, siguiente salto, salida y ruta por defecto         |
| **Elegir ruta RIP**            | Cuenta saltos y elige el menor número; comprueba empates                                        |
| **Elegir ruta OSPF del guion** | Suma `1/BW` por cada enlace de cada camino y elige el menor total                               |
| **Fragmentación IPv4**         | MTU − cabecera → ajusta múltiplo de 8 → divide datos → offset en bloques de 8 → asigna MF       |
| **Comprobar una ruta**         | Sigue un paquete salto a salto usando la coincidencia de prefijo más largo                      |

*Material base: Seminario3_FR.pdf, Seminario3_FR_solucion.pdf y Seminario3_FR_autonomo.pdf (curso 2026/27). Las salvedades técnicas se han identificado explícitamente donde afectan al estudio.*
