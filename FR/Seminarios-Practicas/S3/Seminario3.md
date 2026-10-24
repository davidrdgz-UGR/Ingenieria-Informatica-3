# SEMINARIO 3 / PDF 1 - RESUELTO

## PROBLEMA 1

Departamento A ->   90 ordenadores, conectado al router RC.
Departamento B ->   90 ordenadores, conectado al router RD.
Red WiFi ->         visitantes conectados mediante RE.
Router RB ->        interconecta la parte interna con la Extranet.
Extranet: ->        servidores WEB y MAIL, y conexión con RA.
Router RA ->        salida hacia Internet.
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

| Red | Subred | Máscara | IP tilizables | Broadcast |
| --- | --- | --- | --- | --- |
| Departamento A | 192.168.0.0/25 | 255.255.255.128 | .1 – .126 | 192.168.0.127 |
| Departamento B | 192.168.1.0/25 | 255.255.255.128 | .1 – .126 | 192.168.1.127 |
| WiFi | 192.168.2.0/25 | 255.255.255.0 | .1 – .254 | 192.168.2.255 |
| Routers RC-RD-RE-RB | 192.168.3.0/25 | 255.255.255.248 | .1 - .6 | 192.168.3.7 |
| Extranet | 192.168.4.0/25 | 255.255.255.248 | .1 - .6 | 192.168.4.7 |

### Direcciones de las Interfaces de los routers

| Router | Interfaz | Salto | Interfaz |
| --- | --- | --- | --- |
| RC | eth1 | `192.168.0.1/25` | Departamento A |
| RC | eth0 | `192.168.3.2/29` | Red entre routers |
| RD | eth1 | `192.168.1.1/25` | Departamento B |
| RD | eth0 | `192.168.3.3/29` | Red entre routers |
| RE | eth1 / lado WiFi | `192.168.2.1/24` | Visitantes |
| RE | eth0 | `192.168.3.4/29` | Red entre routers |
| RB | eth1 | `192.168.3.1/29` | Red entre routers |
| RB | eth0 | `192.168.4.1/29` | Extranet |
| RA | eth1 | `192.168.4.2/29` | Extranet |
| RA | eth0 | `44.44.44.44` | Salida pública a Internet |
| Servidor WEB | Interfaz de red | `192.168.4.3/29` | Extranet |
| Servidor MAIL | Interfaz de red | `192.168.4.4/29` | Extranet |


### Tablas de Enrutamiento

#### Router RC

| Destino | Máscara | Siguiente salto | Interfaz | Explicación |
| --- | --- | --- | --- | --- |
| `192.168.3.0` | /29 | Directa | eth0 | Red RB-RC-RD-RE |
| `192.168.0.0` | /25 | Directa | eth1 | Departamento A |
| `0.0.0.0` | /0 | `192.168.3.1` | eth0 | El resto se envía a RB |

#### Router RD

| Destino | Máscara | Siguiente salto | Interfaz | Explicación |
| --- | --- | --- | --- | --- |
| `192.168.3.0` | /29 | Directa | eth0 | Red RB-RC-RD-RE |
| `192.168.1.0` | /25 | Directa | eth1 | Departamento B |
| `0.0.0.0` | /0 | `192.168.3.1` | eth0 | El resto se envía a RB |

#### Router RE

| Destino | Máscara | Siguiente salto | Interfaz | Explicación |
| --- | --- | --- | --- | --- |
| `192.168.3.0` | /29 | Directa | eth0 | Red RB-RC-RD-RE |
| `192.168.2.0` | /24 | Directa | eth1 | Red WiFi de visitantes |
| `0.0.0.0` | /0 | `192.168.3.1` | eth0 | El resto se envía a RB |

#### Router RB

| Destino | Máscara | Siguiente salto | Interfaz | Explicación |
| --- | --- | --- | --- | --- |
| `192.168.4.0` | /29 | Directa | eth0 | Extranet con RA y servidores |
| `192.168.3.0` | /29 | Directa | eth1 | Red entre RB, RC, RD y RE |
| `192.168.0.0` | /25 | `192.168.3.2` | eth1 | Departamento A, por RC |
| `192.168.1.0` | /25 | `192.168.3.3` | eth1 | Departamento B, por RD |
| `192.168.2.0` | /24 | `192.168.3.4` | eth1 | Visitantes, por RE |
| `0.0.0.0` | /0 | `192.168.4.2` | eth0 | Salida a Internet, por RA |

#### Router RA

| Destino | Máscara | Siguiente salto | Interfaz | Explicación |
| --- | --- | --- | --- | --- |
| `44.44.44.0` | /24* | Directa | eth0 | Enlace con el ISP, **según la tabla del profesor** |
| `192.168.4.0` | /29 | Directa | eth1 | Extranet |
| `192.168.0.0` | /22 | `192.168.4.1` | eth1 | Ruta agregada hacia las redes internas, por RB |
| `0.0.0.0` | /0 | Gateway ISP (no indicado) | eth0 | Salida general a Internet |

#### Servidores WEB y MAIL

| Destino | Máscara | Siguiente salto | Interfaz | Explicación |
| --- | --- | --- | --- | --- |
| `192.168.4.0` | /29 | Directa | eth0 | Red Extranet |
| `192.168.0.0` | /22 | `192.168.4.1` | eth0 | Redes corporativas, por RB |
| `0.0.0.0` | /0 | `192.168.4.2` | eth0 | Internet, por RA |

## PROBLEMA 2

Tenemos una red con siete routers (R1–R7) y dos equipos, A y B y el objetivo es averiguar qué camino seguirá un paquete desde A hasta B utilizando dos protocolos diferentes:

RIP: busca el camino con menor número de saltos.

OSPF: busca el camino con menor coste, calculado a partir del ancho de banda de los enlaces.

###  RIP

|Camino entre routers | Saltos |
| --- | --- |
|R1 → R2 → R4 → R5 | 3 |
|R1 → R6 → R4 → R5 | 3 |
|R1 → R6 → R7 → R5 | 3 |
|R1 → R2 → R3 → R4 → R5 | 4 |

En RIP no se tiene información de las velocidades, por lo que las 3 primeras rutas con 3 saltos son iguales de coste.

###  OSPF

Aqui usamos una fórmula de coste de camino. ( Sumatorio(ᵢ) de 1 / BWᵢ )

BWᵢ -> Ancho de Banda en Mbps de cada enlace

Camino 1: 1/5 + 1/3 + 1/5 = 0.733

Camino 2: 1/4 + 1/10 + 1/5 = 0.55

Camino 3: 1/4 + 1/4 + 1/4 = 0.75

Camino 4: 1/5 + 1/5 + 1/5 + 1/5 = 0.8

Camino 5: 1/4 + 1/4 + 1/25 + 1/5 = 0.74

Por lo que el camino más óptimo es el Camino 2 ( 0.55 )


### PROBLEMA 3



