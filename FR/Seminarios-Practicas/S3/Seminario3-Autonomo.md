# SEMINARIO 3 / PDF 2 - AUTÓNOMO

## PROBLEMA 1

8 redes finales: A, B, C, D, E, F, G y H.           \
7 routers: R0, R1, R2, R3, R4, R5 y R6.             \
Rango público disponible: 168.168.168.0/22.         \
Enlace con Internet: 33.33.33.0/30.

Red A   ->	25  Hosts       \
Red B   ->	25  Hosts       \
Red C   ->	50  Hosts       \
Red D   ->	50  Hosts       \
Red E   ->	100 Hosts       \
Red F   ->	100 Hosts       \
Red G   ->	200 Hosts       \
Red H   ->	200 Hosts

Total	->  750 Hosts

Red A/B ( 25 Host + 1 router = 26 Ip cada una )     ->  2⁵ - 2 = 30     -> 32 - 5 = /27     (255.255.255.224)       \
Red C/D ( 50 Host + 1 router = 51 Ip cada una )     ->  2⁶ - 2 = 62     -> 32 - 6 = /26     (255.255.255.192)       \
Red E/F ( 100 Host + 1 router = 101 Ip cada una )   ->  2⁷ - 2 = 126    -> 32 - 7 = /25     (255.255.255.128)       \
Red G/H ( 200 Host + 1 router = 201 Ip cada una )   ->  2⁸ - 2 = 254    -> 32 - 8 = /24     (255.255.255.0)       

| Red |   Hosts   | Prefijo | IP utilizables |
| --- | --------- | ------- | -------------- |
| A   | 25 + 1    | /27     | 30             |
| B   | 25 + 1    | /27     | 30             |
| C   | 50 + 1    | /26     | 62             |
| D   | 50 + 1    | /26     | 62             |
| E   | 100 + 1   | /25     | 126            |
| F   | 100 + 1   | /25     | 126            |
| G   | 200 + 1   | /24     | 254            |
| H   | 200 + 1   | /24     | 254            |

`32*2 + 64*2 + 128*2 + 256*2 = 960 direcciones`

El rango que tenemos es 168.168.168.0/22 -> 2¹⁰ = 1024 -> 1024 - 960 = 64 direcciones libres ( Nos sirve )


### ASIGNACION DE IPs

Agrupamos las subredes en parejas por cada router y la cantidad de direcciones necesarias. ( R3 - A/B , R4 - C/D, R5 - E/F, R6 - H/G)

| Red | Router | Subred asignada      | Máscara         |  IP utilizables        | Broadcast       |
| --- | ------ | -------------------- | --------------- |  ------------------    | --------------- |
| G   | R6     | `168.168.168.0/24`   | 255.255.255.0   |  .168.1   – .168.254   | 168.168.168.255 |
| H   | R6     | `168.168.169.0/24`   | 255.255.255.0   |  .169.1   – .169.254   | 168.168.169.255 |
| E   | R5     | `168.168.170.0/25`   | 255.255.255.128 |  .170.1   – .170.126   | 168.168.170.127 |
| F   | R5     | `168.168.170.128/25` | 255.255.255.128 |  .170.129 – .170.254   | 168.168.170.255 |
| C   | R4     | `168.168.171.0/26`   | 255.255.255.192 |  .171.1   – .171.62    | 168.168.171.63  |
| D   | R4     | `168.168.171.64/26`  | 255.255.255.192 |  .171.65  – .171.126   | 168.168.171.127 |
| A   | R3     | `168.168.171.128/27` | 255.255.255.224 |  .171.129 – .171.158   | 168.168.171.159 |
| B   | R3     | `168.168.171.160/27` | 255.255.255.224 |  .171.161 – .171.190   | 168.168.171.191 |


De las 64 direcciones restantes reservamos 6 subredes /30 para conectar los routers.

Cada subred ocupa 4 direcciones (2 utilizables).

6 × 4 = 24 direcciones reservadas.

64 - 24 = 40 direcciones que quedan libres.

### SUBREDES ENTRE ROUTERS

R3/R4 - R1		\
R5/R6 - R2		\
R1/R2 - R0		\

Usaremos máscara /30 -> 255.255.255.252 - ( 4 Direcciones totales / 2 Disponibles)

| Enlace  | Subred               | IP primer router | IP segundo router | Broadcast |
| ------- | -------------------- | ---------------- | ----------------- | --------- |
| R3 ↔ R1 | `168.168.171.192/30` | R3: `.193`       | R1: `.194`        | `.195`    |
| R4 ↔ R1 | `168.168.171.196/30` | R4: `.197`       | R1: `.198`        | `.199`    |
| R5 ↔ R2 | `168.168.171.200/30` | R5: `.201`       | R2: `.202`        | `.203`    |
| R6 ↔ R2 | `168.168.171.204/30` | R6: `.205`       | R2: `.206`        | `.207`    |
| R1 ↔ R0 | `168.168.171.208/30` | R1: `.209`       | R0: `.210`        | `.211`    |
| R2 ↔ R0 | `168.168.171.212/30` | R2: `.213`       | R0: `.214`        | `.215`    |


### ASIGNACION DE INTERFACES

Asignamos una IP a cada interfaz de router que conecta con una red final

| Router | Red | IP de la interfaz | Máscara |
| ------ | --- | ----------------- | ------- |
| R3     | A   | `168.168.171.129` | /27     |
| R3     | B   | `168.168.171.161` | /27     |
| R4     | C   | `168.168.171.1`   | /26     |
| R4     | D   | `168.168.171.65`  | /26     |
| R5     | E   | `168.168.170.1`   | /25     |
| R5     | F   | `168.168.170.129` | /25     |
| R6     | G   | `168.168.168.1`   | /24     |
| R6     | H   | `168.168.169.1`   | /24     |


### CONFIGURACION SALIDA CON INTERNET

Sabemos que R0 -> Internet usa 33.33.33.0/30 Pública ( 4 Ips -> 2 utilizables )

Dirección de Red		 -> 33.33.33.0/30
R0 ( Salida a Internet ) -> 33.33.33.1/30
Router del ISP 			 -> 33.33.33.2/30
Dirección de Broadcast	 -> 33.33.33.3/30


### TABLAS DE ENRUTAMIENTO

Para minimizar las tablas de encaminamiento utilizamos agregación de rutas:

- Redes A + B -> 168.168.171.128/26 (R3)
- Redes C + D -> 168.168.171.0/25 (R4)
- Redes E + F -> 168.168.170.0/24 (R5)
- Redes G + H -> 168.168.168.0/23 (R6)

**Router R3 — Redes A y B**

| Destino         | Máscara | Siguiente salto | Interfaz | Explicación       |
| --------------- | ------- | --------------- | -------- | ----------------- |
| 168.168.171.128 | /27     | Directa         | Red A    | Departamento A    |
| 168.168.171.160 | /27     | Directa         | Red B    | Departamento B    |
| 168.168.171.192 | /30     | Directa         | Hacia R1 | Conexión R3–R1    |
| 0.0.0.0         | /0      | 168.168.171.194 | Hacia R1 | Cualquier otra red|

**Router R4 — Redes C y D**

| Destino         | Máscara | Siguiente salto | Interfaz | Explicación        |
| --------------- | ------- | --------------- | -------- | ------------------ |
| 168.168.171.0   | /26     | Directa         | Red C    | Departamento C     |
| 168.168.171.64  | /26     | Directa         | Red D    | Departamento D     |
| 168.168.171.196 | /30     | Directa         | Hacia R1 | Conexión R4–R1     |
| 0.0.0.0         | /0      | 168.168.171.198 | Hacia R1 | Cualquier otra red |

**Router R5 — Redes E y F**

| Destino         | Máscara | Siguiente salto | Interfaz | Explicación        |
| --------------- | ------- | --------------- | -------- | ------------------ |
| 168.168.170.0   | /25     | Directa         | Red E    | Departamento E     |
| 168.168.170.128 | /25     | Directa         | Red F    | Departamento F     |
| 168.168.171.200 | /30     | Directa         | Hacia R2 | Conexión R5–R2     |
| 0.0.0.0         | /0      | 168.168.171.202 | Hacia R2 | Cualquier otra red |

**Router R6 — Redes G y H**

| Destino         | Máscara | Siguiente salto | Interfaz | Explicación        |
| --------------- | ------- | --------------- | -------- | ------------------ |
| 168.168.168.0   | /24     | Directa         | Red G    | Departamento G     |
| 168.168.169.0   | /24     | Directa         | Red H    | Departamento H     |
| 168.168.171.204 | /30     | Directa         | Hacia R2 | Conexión R6–R2     |
| 0.0.0.0         | /0      | 168.168.171.206 | Hacia R2 | Cualquier otra red |


**Router R1**

| Destino         | Máscara | Siguiente salto | Interfaz | Explicación               |
| --------------- | ------- | --------------- | -------- | ------------------------- |
| 168.168.171.192 | /30     | Directa         | Hacia R3 | Conexión R1–R3            |
| 168.168.171.196 | /30     | Directa         | Hacia R4 | Conexión R1–R4            |
| 168.168.171.208 | /30     | Directa         | Hacia R0 | Conexión R1–R0            |
| 168.168.171.128 | /26     | 168.168.171.193 | Hacia R3 | Agrupa A y B              |
| 168.168.171.0   | /25     | 168.168.171.197 | Hacia R4 | Agrupa C y D              |
| 0.0.0.0         | /0      | 168.168.171.210 | Hacia R0 | Resto de redes e Internet |


**Router R2**

| Destino         | Máscara | Siguiente salto | Interfaz | Explicación               |
| --------------- | ------- | --------------- | -------- | ------------------------- |
| 168.168.171.200 | /30     | Directa         | Hacia R5 | Conexión R2–R5            |
| 168.168.171.204 | /30     | Directa         | Hacia R6 | Conexión R2–R6            |
| 168.168.171.212 | /30     | Directa         | Hacia R0 | Conexión R2–R0            |
| 168.168.170.0   | /24     | 168.168.171.201 | Hacia R5 | Agrupa E y F              |
| 168.168.168.0   | /23     | 168.168.171.205 | Hacia R6 | Agrupa G y H              |
| 0.0.0.0         | /0      | 168.168.171.214 | Hacia R0 | Resto de redes e Internet |


**Router R0 - Salida a Internet**

En R0 utilizamos la regla del prefijo más largo
Si varias rutas coinciden con una IP de destino, se elige la que tenga la máscara más específica.

| Destino         | Máscara | Siguiente salto | Interfaz  | Explicación                              |
| --------------- | ------- | --------------- | --------- | -------------------------------------    |
| 168.168.171.208 | /30     | Directa         | Hacia R1  | Conexión R0–R1                           |
| 168.168.171.212 | /30     | Directa         | Hacia R2  | Conexión R0–R2                           |
| 33.33.33.0      | /30     | Directa         | Hacia ISP | Conexión con Internet                    |
| 168.168.168.0   | /22     | 168.168.171.213 | Hacia R2  | Ruta general hacia el bloque corporativo |
| 168.168.171.0   | /24     | 168.168.171.209 | Hacia R1  | Excepción para las redes de R1           |
| 168.168.171.200 | /29     | 168.168.171.213 | Hacia R2  | Enlaces R2–R5 y R2–R6                    |
| 0.0.0.0         | /0      | 33.33.33.2      | Hacia ISP | Salida a Internet                        |