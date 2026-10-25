## SEMINARIO 3 / PDF 2 - AUTÓNOMO

### PROBLEMA 1

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