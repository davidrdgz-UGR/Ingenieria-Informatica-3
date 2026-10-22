## SEMINARIO 3

## PDF 1 - RESUELTO

### PROBLEMA 1

Departamento A ->   90 ordenadores, conectado al router RC.
Departamento B ->   90 ordenadores, conectado al router RD.
Red WiFi ->         visitantes conectados mediante RE.
Router RB ->        interconecta la parte interna con la Extranet.
Extranet: ->        servidores WEB y MAIL, y conexión con RA.
Router RA ->        salida hacia Internet.
IP pública disp. -> 44.44.44.44.


Tenemos entre los departamentos A y B 180 equipos + Visitantes + Routers + Servidores
Para conectar tantos equipos a la red usaremos direcciones IPv4 privadas y NAT/PAT en el router de salida RA

Para las redes internas usaremos 192.168.0.0/16 como rango privado

- Calculo de tamaño de redes

Departamento A y B: 90 Equipos + 1 Router (91 cada uno)

2^h-2 >= H -> 2^6 - 2 = 62 -> 2^7 - 2 = 126

Como IPv4 tiene 32 bits -> 32 - 7 = 25  

Subred /25 -> Mascara = 255.255.255.128  | 128 Direcciones (126 utilizables)

- Subredes

| Red | Subred | Máscara | IP tilizables | Broadcast |
| --- | --- | --- | --- | --- |
| --- | --- | --- | --- | --- |
| --- | --- | --- | --- | --- |
| --- | --- | --- | --- | --- |
| --- | --- | --- | --- | --- |

- Direcciones de las Interfaces de los routers


- Tablas de Enrutamiento

| Router | Destino | Salto | Interfaz |
| --- | --- | --- | --- |
| --- | --- | --- | --- |
| --- | --- | --- | --- |
| --- | --- | --- | --- |
| --- | --- | --- | --- |
| --- | --- | --- | --- |
| --- | --- | --- | --- |
| --- | --- | --- | --- |


### PROBLEMA 2

### PROBLEMA 3


## PDF 2 - AUTÓNOMO

### PROBLEMA 1
