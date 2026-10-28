# SEMINARIO 3 / PDF 1 - RESUELTO

## PROBLEMA 1

Departamento A ->   90 ordenadores, conectado al router RC.             \
Departamento B ->   90 ordenadores, conectado al router RD.             \
Red WiFi ->         visitantes conectados mediante RE.                  \
Router RB ->        interconecta la parte interna con la Extranet.      \
Extranet: ->        servidores WEB y MAIL, y conexión con RA.           \
Router RA ->        salida hacia Internet.                              \
IP pública disp. -> 44.44.44.44. 


Tenemos entre los departamentos A y B 180 equipos + Visitantes + Routers + Servidores
Para conectar tantos equipos a la red usaremos direcciones IPv4 privadas y NAT/PAT en el router de salida RA

Para las redes internas usaremos 192.168.0.0/16 como rango privado por norma.

### Calculo de tamaño de redes

Departamento A y B: 90 Equipos + 1 Router (91 cada uno)

    2^h-2 >= H -> 2⁶ - 2 = 62 -> 2⁷ - 2 = 126
    Como IPv4 tiene 32 bits -> 32 - 7 = 25  
    Subred /25 -> Mascara = 255.255.255.128  | 128 Direcciones (126 utilizables)


WIFI: Suponemos unos 200 visitantes + Interfaz RE
    
    2⁷ - 2 = 126 | 2⁸ - 2 = 254   -> Máscara: 32 - 8 = /24 -> 255.255.255.0  

Red de interconexión RB-RC-RD-RE -> 4 Interfaces
    
    2² - 2 = 2 | 2³ - 2 = 6 -> Máscara 32 - 3 = /29 -> 255.255.255.248

### **Subredes**

| Red                 | Subred         | Máscara         | IP tilizables | Broadcast     |
| ------------------- | -------------- | --------------- | ------------- | ------------- |
| Departamento A      | 192.168.0.0/25 | 255.255.255.128 | .1 – .126     | 192.168.0.127 |
| Departamento B      | 192.168.1.0/25 | 255.255.255.128 | .1 – .126     | 192.168.1.127 |
| WiFi                | 192.168.2.0/25 | 255.255.255.0   | .1 – .254     | 192.168.2.255 |
| Routers RC-RD-RE-RB | 192.168.3.0/25 | 255.255.255.248 | .1 - .6       | 192.168.3.7   |
| Extranet            | 192.168.4.0/25 | 255.255.255.248 | .1 - .6       | 192.168.4.7   |

### Direcciones de las Interfaces de los routers

| Router        | Interfaz         | Salto            | Interfaz                  |
| ------------- | ---------------- | ---------------- | ------------------------- |
| RC            | eth1             | `192.168.0.1/25` | Departamento A            |
| RC            | eth0             | `192.168.3.2/29` | Red entre routers         |
| RD            | eth1             | `192.168.1.1/25` | Departamento B            |
| RD            | eth0             | `192.168.3.3/29` | Red entre routers         |
| RE            | eth1 / lado WiFi | `192.168.2.1/24` | Visitantes                |
| RE            | eth0             | `192.168.3.4/29` | Red entre routers         |
| RB            | eth1             | `192.168.3.1/29` | Red entre routers         |
| RB            | eth0             | `192.168.4.1/29` | Extranet                  |
| RA            | eth1             | `192.168.4.2/29` | Extranet                  |
| RA            | eth0             | `44.44.44.44`    | Salida pública a Internet |
| Servidor WEB  | Interfaz de red  | `192.168.4.3/29` | Extranet                  |
| Servidor MAIL | Interfaz de red  | `192.168.4.4/29` | Extranet                  |


### Tablas de Enrutamiento

#### Router RC

| Destino       | Máscara | Siguiente salto | Interfaz | Explicación            |
| ------------- | ------- | --------------- | -------- | ---------------------- |
| `192.168.3.0` | /29     | Directa         | eth0     | Red RB-RC-RD-RE        |
| `192.168.0.0` | /25     | Directa         | eth1     | Departamento A         |
| `0.0.0.0`     | /0      | `192.168.3.1`   | eth0     | El resto se envía a RB |

#### Router RD

| Destino       | Máscara | Siguiente salto | Interfaz | Explicación            |
| ------------- | ------- | --------------- | -------- | ---------------------- |
| `192.168.3.0` | /29     | Directa         | eth0     | Red RB-RC-RD-RE        |
| `192.168.1.0` | /25     | Directa         | eth1     | Departamento B         |
| `0.0.0.0`     | /0      | `192.168.3.1`   | eth0     | El resto se envía a RB |

#### Router RE

| Destino       | Máscara | Siguiente salto | Interfaz | Explicación            |
| ------------- | ------- | --------------- | -------- | ---------------------- |
| `192.168.3.0` | /29     | Directa         | eth0     | Red RB-RC-RD-RE        |
| `192.168.2.0` | /24     | Directa         | eth1     | Red WiFi de visitantes |
| `0.0.0.0`     | /0      | `192.168.3.1`   | eth0     | El resto se envía a RB |

#### Router RB

| Destino       | Máscara | Siguiente salto | Interfaz | Explicación                  |
| ------------- | ------- | --------------- | -------- | ---------------------------- |
| `192.168.4.0` | /29     | Directa         | eth0     | Extranet con RA y servidores |
| `192.168.3.0` | /29     | Directa         | eth1     | Red entre RB, RC, RD y RE    |
| `192.168.0.0` | /25     | `192.168.3.2`   | eth1     | Departamento A, por RC       |
| `192.168.1.0` | /25     | `192.168.3.3`   | eth1     | Departamento B, por RD       |
| `192.168.2.0` | /24     | `192.168.3.4`   | eth1     | Visitantes, por RE           |
| `0.0.0.0`     | /0      | `192.168.4.2`   | eth0     | Salida a Internet, por RA    |

#### Router RA

| Destino       | Máscara | Siguiente salto           | Interfaz | Explicación                                        |
| ------------- | ------- | ------------------------- | -------- | -------------------------------------------------- |
| `44.44.44.0`  | /24*    | Directa                   | eth0     | Enlace con el ISP, **según la tabla del profesor** |
| `192.168.4.0` | /29     | Directa                   | eth1     | Extranet                                           |
| `192.168.0.0` | /22     | `192.168.4.1`             | eth1     | Ruta agregada hacia las redes internas, por RB     |
| `0.0.0.0`     | /0      | Gateway ISP (no indicado) | eth0     | Salida general a Internet                          |

#### Servidores WEB y MAIL

| Destino       | Máscara | Siguiente salto | Interfaz | Explicación                |
| ------------- | ------- | --------------- | -------- | -------------------------- |
| `192.168.4.0` | /29     | Directa         | eth0     | Red Extranet               |
| `192.168.0.0` | /22     | `192.168.4.1`   | eth0     | Redes corporativas, por RB |
| `0.0.0.0`     | /0      | `192.168.4.2`   | eth0     | Internet, por RA           |

## PROBLEMA 2

Tenemos una red con siete routers (R1–R7) y dos equipos, A y B y el objetivo es averiguar qué camino seguirá un paquete desde A hasta B utilizando dos protocolos diferentes:

RIP: busca el camino con menor número de saltos.

OSPF: busca el camino con menor coste, calculado a partir del ancho de banda de los enlaces.

###  RIP

| Camino entre routers   | Saltos |
| ---------------------- | ------ |
| R1 → R2 → R4 → R5      | 3      |
| R1 → R6 → R4 → R5      | 3      |
| R1 → R6 → R7 → R5      | 3      |
| R1 → R2 → R3 → R4 → R5 | 4      |

En RIP no se tiene información de las velocidades, por lo que las 3 primeras rutas con 3 saltos son iguales de coste.

###  OSPF

Aqui usamos una fórmula de coste de camino. ( Sumatorio(ᵢ) de 1 / BWᵢ )

BWᵢ -> Ancho de Banda en Mbps de cada enlace

Camino 1: 1/5 + 1/3 + 1/5 = 0.733       \
Camino 2: 1/4 + 1/10 + 1/5 = 0.55       \
Camino 3: 1/4 + 1/4 + 1/4 = 0.75        \
Camino 4: 1/5 + 1/5 + 1/5 + 1/5 = 0.8   \
Camino 5: 1/4 + 1/4 + 1/25 + 1/5 = 0.74 \

Por lo que el camino más óptimo es el Camino 2 ( 0.55 )


## PROBLEMA 3

Tenemos una empresa con tres departamentos, varios servidores, routers y un trabajador que se conecta desde casa.

Red A   ->	                        100 equipos                         \
Red B   ->	                        50 equipos                          \
Red C   ->	                        25 equipos                          \
Servidores ->	                    WEB, FTP1 y MAIL                    \
Routers de la empresa ->	        RX, RA, RB y RC                     \
Router del trabajador ->	        RY                                  \
PC del trabajador ->	            192.168.1.33                        \
IP pública de RY ->	                8.8.8.8                             \
IP pública de RX ->	                4.4.4.4                             \
Rango público de la empresa ->  	150.150.150.0/24                    \   


### Asignar direcciones IP

En este caso tenemos un rango público completo ( 150.150.150.0/24 )

32 - 24 = 8 -> 2⁸ = 256 direcciones

| Subred       | IPs | Cálculo              |
| ------------ | --- | -------------------- |
| A + RA       | 101 | 2⁷ - 2 = 126 ->  /25 |
| B + RB       | 51  | 2⁶ - 2 = 62  ->  /26 |
| C + RC       | 26  | 2⁵ - 2 = 30  ->  /27 |
| RX + 3 Serv. | 4   | 2³ - 2 = 6   ->  /29 |
| RX - RA      | 2   | 2² - 2 = 2   ->  /30 |
| RA - RB      | 2   | 2² - 2 = 2   ->  /30 |
| RB - RC      | 2   | 2² - 2 = 2   ->  /30 |


### Distribución de Redes

| Subred     | Prefijo | Dirección de red | Primera IP | Última IP | Broadcast |
| ---------- | ------- | ---------------- | ---------- | --------- | --------- |
| A          | /25     | 150.150.150.0    | .1         | .126      | .127      |
| B          | /26     | 150.150.150.128  | .129       | .190      | .191      |
| C          | /27     | 150.150.150.192  | .193       | .222      | .223      |
| Servidores | /29     | 150.150.150.224  | .225       | .230      | .231      |
| RX–RA      | /30     | 150.150.150.232  | .233       | .234      | .235      |
| RA–RB      | /30     | 150.150.150.236  | .237       | .238      | .239      |
| RB–RC      | /30     | 150.150.150.240  | .241       | .242      | .243      |

Si sumamos todas las redes -> 128 + 64 + 32 + 8 + 4 + 4 + 4 = 244       \
Por lo que nos quedan 256 - 244 = 12 direcciones libres.


### Fragmentación de Paquetes - PETICIÓN

Si el trabajador quiere acceder al servidor FTP de la Red C envía una petición de 1480 bytes + 20 de cabecera IP ( 1500 bytes ).

**Recorrido:** PC → RY → Internet → RX → RA → RB → RC → Servidor FTP.

- IP origen inicial: 192.168.1.33 (PC del trabajador).
- IP origen después de NAT: 8.8.8.8 (IP pública del router RY).
- IP destino: 150.150.150.193 (Servidor FTP). Se mantiene durante todo el recorrido.
- Puerto origen: 1037 (puerto del cliente).
- Puerto destino: 21 (servicio FTP).
- Identificador del paquete (ID): 1.

- Primer caso ( MTU 1000 Bytes )

El Maximo teorico es 1000-20 = 980, y además cada fragmento no final tiene que ser múltiplo de 8 -> 976 ( 122 * 8 )

Paquete original de 1480 bytes.                                                \
Fragmento 1: 976 datos + 20 cabecera = 996 bytes | Offset : 0     | MF: 1      \
Fragmento 2: 504 datos + 20 cabecera = 524 bytes | Offset : 122   | MF: 0

- Segundo Caso ( MTU 500 bytes )

El maximo es 500-20 = 480 bytes, que ya es múltiplo de 8.

976 = 480 + 480 + 16

504 = 480 + 24

| Fragmento | Datos | Offset | MF  |
| --------- | ----- | ------ | --  |
| 1A        | 480   | 0      | 1   |
| 1B        | 480   | 60     | 1   |
| 1C        | 16    | 120    | 1   |
| 2A        | 480   | 122    | 1   |
| 2B        | 24    | 182    | 0   |




### Fragmentación de Paquetes - RESPUESTA

El servidor FTP de la Red C envía una respuesta de 1480 bytes + 20 de cabecera IP (1500 bytes).

La respuesta atraviesa inicialmente enlaces con MTU de 1500 bytes, por lo que no necesita fragmentarse.


**Recorrido:** Servidor FTP → RC → RB → RA → RX → Internet → RY → PC.

- IP origen: 150.150.150.193 (Servidor FTP). Se mantiene durante todo el recorrido.
- IP destino inicial: 8.8.8.8 (IP pública del router RY).
- IP destino después de NAT: 192.168.1.33 (PC del trabajador).
- Puerto origen: 21 (servicio FTP).
- Puerto destino: 1037 (puerto del cliente).
- Identificador del paquete (ID): 1.

**Primer caso ( MTU 500 Bytes )**

El máximo es 500-20 = 480 bytes, que ya es múltiplo de 8.

Paquete original de 1480 bytes.

1480 = 480 + 480 + 480 + 40

| Fragmento | Datos | Offset | MF  |
| --------- | ----- | ------ | --  |
| 1         | 480   | 0      | 1   |
| 2         | 480   | 60     | 1   |
| 3         | 480   | 120    | 1   |
| 4         | 40    | 180    | 0   |


**Segundo caso ( MTU 1000 y 2000 Bytes )**

Los fragmentos ya son menores que las MTU de los siguientes enlaces, por lo que no se vuelven a fragmentar.

Los routers intermedios no reensamblan los fragmentos. Estos mantienen sus tamaños, offsets y flags MF hasta llegar al PC del trabajador.

El reensamblado se realiza en el dispositivo de destino.