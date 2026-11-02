# GUÍA DE REPASO — PRÁCTICA 1 DE FUNDAMENTOS DE REDES
## Enrutamiento estático y dinámico en Cisco Packet Tracer

**Objetivo:** reproducir la topología del guion, asignar direcciones IP y conseguir conectividad entre todas las redes mediante rutas estáticas, RIP v2, OSPF en área única y OSPF en dos áreas.

**Cómo utilizar esta guía:** está dividida por los **cinco archivos `.pkt` que se entregan**. El archivo de direccionamiento IP es la **base**; cada protocolo se configura en una **copia independiente** de esa base. Las direcciones individuales de los equipos y la distribución de puertos son las que elegimos durante nuestra práctica; el guion fija las subredes, pero no todos esos detalles. Sustituye `DNI` por tu DNI en los nombres de archivo.

---

# 0. MONTAJE DE LA TOPOLOGÍA — RESUMEN INICIAL

## 0.1. Dispositivos utilizados

- **5 routers Cisco ISR 4331:** R1, R2, R3, R4 y R5.
- **5 switches Cisco Catalyst 2960:** Switch0, Switch1, Switch2, Switch3 y Switch4.
- **9 ordenadores:** PC0 a PC8.
- **1 servidor:** Server0.
- **Módulos serie NIM-2T:** en cada router que use enlaces serie. **R3 necesita dos módulos NIM-2T**, ya que utiliza tres interfaces serie.

> **Nota del guion:** en el texto inicial se habla de cuatro routers, pero su esquema completo utiliza **cinco**, incluido R5 (red del servidor). Seguimos el esquema.

## 0.2. Colocación de la estructura

1. Abrir Packet Tracer y crear un proyecto nuevo.
2. Colocar R1, R2, R3 y R4 formando un anillo/cuadrado: R1–R2–R3–R4–R1.
3. Colocar R5 conectado a R3.
4. Colocar un switch en la LAN de cada router, los PCs correspondientes y el servidor junto a PC8.
5. Renombrar los routers como `R1`, `R2`, `R3`, `R4`, `R5` (el nombre del dispositivo en pantalla y el `hostname` de la consola pueden configurarse por separado).
6. Para instalar NIM-2T: abrir router → **Physical** → apagarlo → arrastrar el módulo a una ranura disponible → encenderlo. Los nombres de interfaces pueden cambiar según la ranura elegida; **las tablas siguientes corresponden a las interfaces que usamos nosotros**.

## 0.3. Tipos de cable

| Conexión | Cable | Para qué sirve |
|---|---|---|
| PC / servidor ↔ switch | **Copper Straight-Through** | Tráfico de la LAN local. |
| Switch ↔ router | **Copper Straight-Through** | Comunicación de la LAN con su puerta de enlace. |
| Router ↔ router | **Serial DCE** | Enlace WAN punto a punto entre routers. |
| PC ↔ router | **Console** (azul claro) | Configurar el router desde **Desktop → Terminal** del PC; no transporta tráfico IP normal. |

## 0.4. Puertos: PCs y servidor ↔ switches

| Equipo y puerto | Switch y puerto | Red |
|---|---|---|
| PC2 `FastEthernet0` | Switch0 `FastEthernet0/1` | LAN de R1 |
| PC3 `FastEthernet0` | Switch0 `FastEthernet0/2` | LAN de R1 |
| PC4 `FastEthernet0` | Switch1 `FastEthernet0/1` | LAN de R2 |
| PC5 `FastEthernet0` | Switch1 `FastEthernet0/2` | LAN de R2 |
| PC6 `FastEthernet0` | Switch2 `FastEthernet0/1` | LAN de R3 |
| PC7 `FastEthernet0` | Switch2 `FastEthernet0/2` | LAN de R3 |
| PC0 `FastEthernet0` | Switch3 `FastEthernet0/1` | LAN de R4 |
| PC1 `FastEthernet0` | Switch3 `FastEthernet0/2` | LAN de R4 |
| PC8 `FastEthernet0` | Switch4 `FastEthernet0/1` | LAN de R5 |
| Server0 `FastEthernet0` | Switch4 `FastEthernet0/2` | LAN de R5 |

## 0.5. Puertos: switches ↔ routers

Todas estas conexiones utilizan **cobre directo**.

| Switch y puerto | Router y puerto |
|---|---|
| Switch0 `GigabitEthernet0/1` | R1 `GigabitEthernet0/0/0` |
| Switch1 `GigabitEthernet0/1` | R2 `GigabitEthernet0/0/0` |
| Switch2 `GigabitEthernet0/1` | R3 `GigabitEthernet0/0/0` |
| Switch3 `GigabitEthernet0/1` | R4 `GigabitEthernet0/0/0` |
| Switch4 `GigabitEthernet0/1` | R5 `GigabitEthernet0/0/0` |

## 0.6. Puertos: routers ↔ routers

Todas estas conexiones utilizan **cable serie**.

| Primer extremo | Segundo extremo | Subred del enlace |
|---|---|---|
| R1 `Serial0/1/0` | R2 `Serial0/1/0` | `10.10.1.0/30` (WAN R1–R2) |
| R2 `Serial0/1/1` | R3 `Serial0/1/0` | `10.10.2.0/30` (WAN R2–R3) |
| R3 `Serial0/1/1` | R4 `Serial0/1/0` | `10.10.3.0/30` (WAN R3–R4) |
| R4 `Serial0/1/1` | R1 `Serial0/1/1` | `10.10.4.0/30` (WAN R4–R1) |
| R3 `Serial0/2/0` | R5 `Serial0/1/0` | `10.10.5.0/30` (WAN R3–R5) |

## 0.7. Puertos de consola

| Ordenador y puerto | Router y puerto |
|---|---|
| PC3 `RS232` | R1 `Console` |
| PC4 `RS232` | R2 `Console` |
| PC7 `RS232` | R3 `Console` |
| PC1 `RS232` | R4 `Console` |
| PC8 `RS232` | R5 `Console` |

**Uso de la consola:** PC → **Desktop → Terminal** → aceptar configuración → Enter. Para mandar un `ping` desde el propio PC, se utiliza **Desktop → Command Prompt** (es una ventana diferente).

---

# 1. PRIMER ARCHIVO — `DNI_P1_IP.pkt`
## Apartado 3 del guion: Direccionamiento IP

### 1.1. Qué conseguimos

Asignamos la IP de cada PC/servidor, la IP de la interfaz LAN de cada router y las IP de todos los enlaces serie. Al final, las interfaces utilizadas deben estar **up/up** y los dispositivos deben comunicarse con otros de **su misma red**. Todavía no configuramos rutas para llegar a redes remotas.

### 1.2. Direcciones de las redes según el guion

| Subred | Identifica a |
|---|---|
| `192.168.10.0/24` | LAN de R1 (PC2 y PC3) |
| `192.168.20.0/24` | LAN de R2 (PC4 y PC5) |
| `192.168.30.0/24` | LAN de R3 (PC6 y PC7) |
| `192.168.40.0/24` | LAN de R4 (PC0 y PC1) |
| `172.29.0.0/16` | LAN de R5 (PC8 y Server0; el guion la describe como red del servidor) |
| `10.10.1.0/30` | WAN entre R1 y R2 |
| `10.10.2.0/30` | WAN entre R2 y R3 |
| `10.10.3.0/30` | WAN entre R3 y R4 |
| `10.10.4.0/30` | WAN entre R4 y R1 |
| `10.10.5.0/30` | WAN entre R3 y R5 |

**Máscaras:** `/24` = `255.255.255.0`; `/16` = `255.255.0.0`; `/30` = `255.255.255.252`. Una WAN `/30` tiene dos direcciones utilizables: una para cada router.

### 1.3. Configurar PCs y servidor

Ruta en cada equipo: **Desktop → IP Configuration → Static**. El DNS se dejó sin configurar (`0.0.0.0`).

| Equipo | IP asignada (de quién es) | Máscara | Puerta de enlace (router correspondiente) |
|---|---|---|---|
| PC2 | `192.168.10.10` (**PC2**, LAN R1) | `255.255.255.0` | `192.168.10.1` (**R1**) |
| PC3 | `192.168.10.11` (**PC3**, LAN R1) | `255.255.255.0` | `192.168.10.1` (**R1**) |
| PC4 | `192.168.20.10` (**PC4**, LAN R2) | `255.255.255.0` | `192.168.20.1` (**R2**) |
| PC5 | `192.168.20.11` (**PC5**, LAN R2) | `255.255.255.0` | `192.168.20.1` (**R2**) |
| PC6 | `192.168.30.10` (**PC6**, LAN R3) | `255.255.255.0` | `192.168.30.1` (**R3**) |
| PC7 | `192.168.30.11` (**PC7**, LAN R3) | `255.255.255.0` | `192.168.30.1` (**R3**) |
| PC0 | `192.168.40.10` (**PC0**, LAN R4) | `255.255.255.0` | `192.168.40.1` (**R4**) |
| PC1 | `192.168.40.11` (**PC1**, LAN R4) | `255.255.255.0` | `192.168.40.1` (**R4**) |
| PC8 | `172.29.0.10` (**PC8**, LAN R5) | `255.255.0.0` | `172.29.0.1` (**R5**) |
| Server0 | `172.29.0.20` (**servidor**, LAN R5) | `255.255.0.0` | `172.29.0.1` (**R5**) |

**Por qué:** la IP identifica al equipo, la máscara determina su red y la puerta de enlace (`.1` de cada LAN) es la interfaz del router que permitirá salir a otras redes.

### 1.4. Comandos utilizados en este apartado — significado ANTES de aplicarlos

| Comando | Qué hace y cuándo se usa |
|---|---|
| `enable` | Pasa de `Router>` a `Router#` (modo privilegiado). |
| `configure terminal` | Entra en configuración global (`Router(config)#`). |
| `hostname R1` | Cambia el nombre que aparece en la consola; opcional, repetir con el nombre de cada router. |
| `interface gigabitEthernet 0/0/0` | Selecciona la interfaz Ethernet hacia el switch de la LAN. |
| `interface serial 0/1/0` | Selecciona una interfaz serie hacia otro router; el número cambia según el enlace. |
| `ip address IP MASCARA` | Asigna IP y máscara a la interfaz seleccionada. |
| `no shutdown` | Habilita la interfaz; sin él puede permanecer administrativamente apagada. |
| `exit` | Sale un nivel de configuración. |
| `end` | Vuelve directamente al modo privilegiado (`Router#`). |
| `write memory` | Guarda la configuración en memoria persistente. |
| `show ip interface brief` | Muestra IP, estado físico (`Status`) y estado del protocolo (`Protocol`) de cada interfaz. |
| `ping IP` | Prueba si se puede alcanzar esa dirección IP. |

### 1.5. Configuración de interfaces LAN: R1 a R5

Para abrir una consola de cada router: R1 desde **PC3**, R2 desde **PC4**, R3 desde **PC7**, R4 desde **PC1**, R5 desde **PC8**.

**R1 — LAN de PC2 y PC3**

```cisco
enable
configure terminal
hostname R1
interface gigabitEthernet 0/0/0
ip address 192.168.10.1 255.255.255.0
no shutdown
end
write memory
```

`192.168.10.1` = **R1**, puerta de enlace de **PC2 y PC3**.

**R2 — LAN de PC4 y PC5**

```cisco
enable
configure terminal
hostname R2
interface gigabitEthernet 0/0/0
ip address 192.168.20.1 255.255.255.0
no shutdown
end
write memory
```

`192.168.20.1` = **R2**, puerta de enlace de **PC4 y PC5**.

**R3 — LAN de PC6 y PC7**

```cisco
enable
configure terminal
hostname R3
interface gigabitEthernet 0/0/0
ip address 192.168.30.1 255.255.255.0
no shutdown
end
write memory
```

`192.168.30.1` = **R3**, puerta de enlace de **PC6 y PC7**.

**R4 — LAN de PC0 y PC1**

```cisco
enable
configure terminal
hostname R4
interface gigabitEthernet 0/0/0
ip address 192.168.40.1 255.255.255.0
no shutdown
end
write memory
```

`192.168.40.1` = **R4**, puerta de enlace de **PC0 y PC1**.

**R5 — LAN de PC8 y servidor**

```cisco
enable
configure terminal
hostname R5
interface gigabitEthernet 0/0/0
ip address 172.29.0.1 255.255.0.0
no shutdown
end
write memory
```

`172.29.0.1` = **R5**, puerta de enlace de **PC8 y Server0**.

### 1.6. Configuración de interfaces serie: R1 a R5

Estas IP identifican **los extremos de cada enlace entre routers**, no los PCs.

| Router / interfaz | IP asignada (extremo) | A qué otro extremo llega |
|---|---|---|
| R1 `S0/1/0` | `10.10.1.1` (**R1 hacia R2**) | `10.10.1.2` (**R2**) |
| R1 `S0/1/1` | `10.10.4.2` (**R1 hacia R4**) | `10.10.4.1` (**R4**) |
| R2 `S0/1/0` | `10.10.1.2` (**R2 hacia R1**) | `10.10.1.1` (**R1**) |
| R2 `S0/1/1` | `10.10.2.1` (**R2 hacia R3**) | `10.10.2.2` (**R3**) |
| R3 `S0/1/0` | `10.10.2.2` (**R3 hacia R2**) | `10.10.2.1` (**R2**) |
| R3 `S0/1/1` | `10.10.3.1` (**R3 hacia R4**) | `10.10.3.2` (**R4**) |
| R3 `S0/2/0` | `10.10.5.1` (**R3 hacia R5**) | `10.10.5.2` (**R5**) |
| R4 `S0/1/0` | `10.10.3.2` (**R4 hacia R3**) | `10.10.3.1` (**R3**) |
| R4 `S0/1/1` | `10.10.4.1` (**R4 hacia R1**) | `10.10.4.2` (**R1**) |
| R5 `S0/1/0` | `10.10.5.2` (**R5 hacia R3**) | `10.10.5.1` (**R3**) |

**R1**

```cisco
enable
configure terminal
interface serial 0/1/0
ip address 10.10.1.1 255.255.255.252
no shutdown
exit
interface serial 0/1/1
ip address 10.10.4.2 255.255.255.252
no shutdown
end
write memory
```

**R2**

```cisco
enable
configure terminal
interface serial 0/1/0
ip address 10.10.1.2 255.255.255.252
no shutdown
exit
interface serial 0/1/1
ip address 10.10.2.1 255.255.255.252
no shutdown
end
write memory
```

**R3**

```cisco
enable
configure terminal
interface serial 0/1/0
ip address 10.10.2.2 255.255.255.252
no shutdown
exit
interface serial 0/1/1
ip address 10.10.3.1 255.255.255.252
no shutdown
exit
interface serial 0/2/0
ip address 10.10.5.1 255.255.255.252
no shutdown
end
write memory
```

**R4**

```cisco
enable
configure terminal
interface serial 0/1/0
ip address 10.10.3.2 255.255.255.252
no shutdown
exit
interface serial 0/1/1
ip address 10.10.4.1 255.255.255.252
no shutdown
end
write memory
```

**R5**

```cisco
enable
configure terminal
interface serial 0/1/0
ip address 10.10.5.2 255.255.255.252
no shutdown
end
write memory
```

**Incidencia que resolvimos:** al configurar solo R1, sus interfaces serie aparecían `down/down`; después de configurar y activar los extremos vecinos, **todas las interfaces utilizadas quedaron `up/up`**. El `clock rate` solo sería necesario si faltase reloj en un extremo DCE; en nuestra práctica no hubo que configurarlo manualmente.

### 1.7. Comprobación y primera entrega

En cada router:

```cisco
show ip interface brief
```

Las interfaces Ethernet y serie que usamos deben aparecer **`up/up`**. Las que no usamos pueden figurar `unassigned` y `administratively down`.

En los PCs, comprobar la comunicación local (Desktop → Command Prompt):

```text
PC2: ping 192.168.10.11    (PC3, misma LAN de R1)
PC4: ping 192.168.20.11    (PC5, misma LAN de R2)
PC6: ping 192.168.30.11    (PC7, misma LAN de R3)
PC0: ping 192.168.40.11    (PC1, misma LAN de R4)
PC8: ping 172.29.0.20      (Server0, misma LAN de R5)
PC2: ping 192.168.10.1     (interfaz LAN de R1)
```

**Archivo a guardar:** `DNI_P1_IP.pkt`.

---

# 2. SEGUNDO ARCHIVO — `DNI_P1_Estatico.pkt`
## Apartado 4.1 del guion: Enrutamiento estático

### 2.1. Preparación y objetivo

Abrir **`DNI_P1_IP.pkt`**, crear una **copia** y llamarla `DNI_P1_Estatico.pkt`. Así conservamos intacta la base.

**Qué hacemos:** cada router conoce sus redes directamente conectadas, pero **no las remotas**. Introducimos manualmente rutas con una red de destino y un **siguiente salto**, es decir, la IP del router vecino al que enviar el paquete. También hay que disponer de una ruta de vuelta.

### 2.2. Comandos utilizados — significado ANTES de aplicarlos

| Comando | Qué significa |
|---|---|
| `enable` / `configure terminal` | Acceder al modo privilegiado y de configuración, respectivamente. |
| `ip route RED MASCARA SIGUIENTE_SALTO` | Añade manualmente una ruta hasta `RED` a través de la IP de un router vecino. |
| `no ip route RED MASCARA SIGUIENTE_SALTO` | Elimina una ruta estática concreta si nos equivocamos o queremos cambiarla. |
| `end` / `write memory` | Salir de configuración y guardar. |
| `show ip route` | Ver todas las rutas; `C` = conectada, `L` = dirección local, `S` = estática, `R` = RIP, `O` = OSPF. |
| `show ip route static` | Ver solamente las rutas estáticas (marcadas con `S`). |
| `ping IP` | Comprobar comunicación y, de forma indirecta, rutas de ida y vuelta. |

### 2.3. Rutas hacia las LAN remotas

**R1 — Redes de R2, R3, R4 y R5**

```cisco
enable
configure terminal
ip route 192.168.20.0 255.255.255.0 10.10.1.2
ip route 192.168.30.0 255.255.255.0 10.10.1.2
ip route 192.168.40.0 255.255.255.0 10.10.4.1
ip route 172.29.0.0 255.255.0.0 10.10.1.2
end
write memory
```

- `192.168.20.0` (**LAN R2, PC4/PC5**) → `10.10.1.2` (**R2, vecino de R1**).
- `192.168.30.0` (**LAN R3, PC6/PC7**) → `10.10.1.2` (**R2, camino hacia R3**).
- `192.168.40.0` (**LAN R4, PC0/PC1**) → `10.10.4.1` (**R4, vecino de R1**).
- `172.29.0.0` (**LAN R5, PC8/Server0**) → `10.10.1.2` (**R2, camino hacia R3 y R5**).

**R2 — Redes de R1, R3, R4 y R5**

```cisco
enable
configure terminal
ip route 192.168.10.0 255.255.255.0 10.10.1.1
ip route 192.168.30.0 255.255.255.0 10.10.2.2
ip route 192.168.40.0 255.255.255.0 10.10.2.2
ip route 172.29.0.0 255.255.0.0 10.10.2.2
end
write memory
```

- `192.168.10.0` (**LAN R1, PC2/PC3**) → `10.10.1.1` (**R1**).
- `192.168.30.0` (**LAN R3, PC6/PC7**) → `10.10.2.2` (**R3**).
- `192.168.40.0` (**LAN R4, PC0/PC1**) → `10.10.2.2` (**R3, camino hacia R4**).
- `172.29.0.0` (**LAN R5, PC8/Server0**) → `10.10.2.2` (**R3, camino hacia R5**).

**R3 — Redes de R1, R2, R4 y R5**

```cisco
enable
configure terminal
ip route 192.168.10.0 255.255.255.0 10.10.2.1
ip route 192.168.20.0 255.255.255.0 10.10.2.1
ip route 192.168.40.0 255.255.255.0 10.10.3.2
ip route 172.29.0.0 255.255.0.0 10.10.5.2
end
write memory
```

- `192.168.10.0` (**LAN R1**) → `10.10.2.1` (**R2, camino hacia R1**).
- `192.168.20.0` (**LAN R2**) → `10.10.2.1` (**R2**).
- `192.168.40.0` (**LAN R4**) → `10.10.3.2` (**R4**).
- `172.29.0.0` (**LAN R5**) → `10.10.5.2` (**R5**).

**R4 — Redes de R1, R2, R3 y R5**

```cisco
enable
configure terminal
ip route 192.168.10.0 255.255.255.0 10.10.4.2
ip route 192.168.20.0 255.255.255.0 10.10.3.1
ip route 192.168.30.0 255.255.255.0 10.10.3.1
ip route 172.29.0.0 255.255.0.0 10.10.3.1
end
write memory
```

- `192.168.10.0` (**LAN R1**) → `10.10.4.2` (**R1**).
- `192.168.20.0` (**LAN R2**) → `10.10.3.1` (**R3, camino hacia R2**).
- `192.168.30.0` (**LAN R3**) → `10.10.3.1` (**R3**).
- `172.29.0.0` (**LAN R5**) → `10.10.3.1` (**R3, camino hacia R5**).

**R5 — Redes de R1, R2, R3 y R4**

```cisco
enable
configure terminal
ip route 192.168.10.0 255.255.255.0 10.10.5.1
ip route 192.168.20.0 255.255.255.0 10.10.5.1
ip route 192.168.30.0 255.255.255.0 10.10.5.1
ip route 192.168.40.0 255.255.255.0 10.10.5.1
end
write memory
```

- Todas las redes LAN remotas (`192.168.10.0` de **R1**, `.20.0` de **R2**, `.30.0` de **R3**, `.40.0` de **R4**) usan `10.10.5.1` (**R3**), porque **R5 solo tiene un vecino router: R3**.

### 2.4. Rutas hacia redes WAN remotas

**Por qué añadimos esto:** para cumplir el requisito de llegar a todas las redes, no solo a las LAN de los PCs. No hace falta añadir una ruta estática a una WAN directamente conectada: ya aparece como `C`.

**R1**

```cisco
enable
configure terminal
ip route 10.10.2.0 255.255.255.252 10.10.1.2
ip route 10.10.3.0 255.255.255.252 10.10.4.1
ip route 10.10.5.0 255.255.255.252 10.10.1.2
end
write memory
```

- `10.10.2.0` (**WAN R2–R3**) → `10.10.1.2` (**R2**).
- `10.10.3.0` (**WAN R3–R4**) → `10.10.4.1` (**R4**).
- `10.10.5.0` (**WAN R3–R5**) → `10.10.1.2` (**R2**).

**R2**

```cisco
enable
configure terminal
ip route 10.10.3.0 255.255.255.252 10.10.2.2
ip route 10.10.4.0 255.255.255.252 10.10.1.1
ip route 10.10.5.0 255.255.255.252 10.10.2.2
end
write memory
```

- `10.10.3.0` (**WAN R3–R4**) → `10.10.2.2` (**R3**).
- `10.10.4.0` (**WAN R4–R1**) → `10.10.1.1` (**R1**).
- `10.10.5.0` (**WAN R3–R5**) → `10.10.2.2` (**R3**).

**R3**

```cisco
enable
configure terminal
ip route 10.10.1.0 255.255.255.252 10.10.2.1
ip route 10.10.4.0 255.255.255.252 10.10.3.2
end
write memory
```

- `10.10.1.0` (**WAN R1–R2**) → `10.10.2.1` (**R2**).
- `10.10.4.0` (**WAN R4–R1**) → `10.10.3.2` (**R4**).

**R4**

```cisco
enable
configure terminal
ip route 10.10.1.0 255.255.255.252 10.10.4.2
ip route 10.10.2.0 255.255.255.252 10.10.3.1
ip route 10.10.5.0 255.255.255.252 10.10.3.1
end
write memory
```

- `10.10.1.0` (**WAN R1–R2**) → `10.10.4.2` (**R1**).
- `10.10.2.0` (**WAN R2–R3**) → `10.10.3.1` (**R3**).
- `10.10.5.0` (**WAN R3–R5**) → `10.10.3.1` (**R3**).

**R5**

```cisco
enable
configure terminal
ip route 10.10.1.0 255.255.255.252 10.10.5.1
ip route 10.10.2.0 255.255.255.252 10.10.5.1
ip route 10.10.3.0 255.255.255.252 10.10.5.1
ip route 10.10.4.0 255.255.255.252 10.10.5.1
end
write memory
```

- `10.10.1.0` (**WAN R1–R2**), `10.10.2.0` (**WAN R2–R3**), `10.10.3.0` (**WAN R3–R4**) y `10.10.4.0` (**WAN R4–R1**) → `10.10.5.1` (**R3, único vecino de R5**).

### 2.5. Comprobaciones y segunda entrega

En los routers:

```cisco
show ip route static
```

La letra `S` marca las rutas estáticas. En nuestra comprobación real de **R3** aparecieron **6 rutas estáticas**: las cuatro LAN remotas y dos WAN remotas. Además, R3 conoce cuatro redes conectadas directamente, completando las diez subredes del guion.

Pruebas entre LAN (Desktop → Command Prompt):

```text
PC2: ping 192.168.20.10    (PC4, LAN de R2)
PC2: ping 192.168.30.10    (PC6, LAN de R3)
PC2: ping 192.168.40.10    (PC0, LAN de R4)
PC2: ping 172.29.0.20      (Server0, LAN de R5)
PC4: ping 192.168.40.10    (PC0, LAN de R4)
PC0: ping 172.29.0.10      (PC8, LAN de R5)
```

Pruebas hacia WAN remotas:

```text
R1: ping 10.10.5.2         (interfaz serie de R5)
R2: ping 10.10.4.1         (interfaz serie de R4)
R3: ping 10.10.1.1         (interfaz serie de R1)
R5: ping 10.10.3.2         (interfaz serie de R4)
```

Si el primer `ping` pierde algún paquete, repetirlo para descartar la resolución ARP inicial. Guardar configuración y escenario como **`DNI_P1_Estatico.pkt`**.

---

# 3. TERCER ARCHIVO — `DNI_P1_Dinamico.pkt`
## Apartado 4.2 del guion: RIP versión 2

### 3.1. Preparación y objetivo

Volver a abrir **`DNI_P1_IP.pkt`** (sin rutas estáticas), guardar una copia como **`DNI_P1_Dinamico.pkt`**. No continuar sobre el archivo estático.

**Qué hacemos:** RIP aprende redes remotas intercambiando información entre routers. En vez de escribir todas las rutas `ip route`, indicamos las **redes conectadas** que participan en RIP. RIPv2 tiene en cuenta las máscaras y utiliza el **número de saltos** como métrica.

### 3.2. Comandos utilizados — significado ANTES de aplicarlos

| Comando | Qué significa |
|---|---|
| `router rip` | Activa el modo de configuración del protocolo RIP. |
| `version 2` | Selecciona RIP versión 2. |
| `network 192.168.10.0` (u otra LAN) | Incluye la interfaz de esa LAN en el proceso RIP y permite anunciar la red. |
| `network 10.0.0.0` | Incluye las interfaces serie `10.10.x.x` en RIP; Cisco usa esta **red principal clase A** en este comando, aunque cada enlace real sea `/30`. |
| `no auto-summary` | Desactiva el resumen automático por clases; se conservan las rutas con sus máscaras. |
| `show ip protocols` | Comprueba protocolos, versión y redes configuradas. |
| `show ip route` | Muestra la tabla completa; las rutas RIP se identifican con `R`. |
| `show ip route rip` | Filtra las rutas aprendidas por RIP. |
| `end` / `write memory` | Sale de configuración y guarda. |

**Cómo leer una ruta:** `R 192.168.20.0/24 [120/1] via 10.10.1.2` significa: `R` = RIP, `192.168.20.0` = LAN de **R2**, `120` = distancia administrativa de RIP, `1` = un salto, `10.10.1.2` = **interfaz de R2**, próximo router.

### 3.3. Configuración de cada router

**R1**

```cisco
enable
configure terminal
router rip
version 2
network 192.168.10.0
network 10.0.0.0
no auto-summary
end
write memory
```

`192.168.10.0` = **LAN de R1** (PC2 y PC3). `10.0.0.0` = **conexiones WAN de R1 hacia R2 y R4**.

**R2**

```cisco
enable
configure terminal
router rip
version 2
network 192.168.20.0
network 10.0.0.0
no auto-summary
end
write memory
```

`192.168.20.0` = **LAN de R2** (PC4 y PC5). `10.0.0.0` = **WAN de R2 hacia R1 y R3**.

**R3**

```cisco
enable
configure terminal
router rip
version 2
network 192.168.30.0
network 10.0.0.0
no auto-summary
end
write memory
```

`192.168.30.0` = **LAN de R3** (PC6 y PC7). `10.0.0.0` = **WAN de R3 hacia R2, R4 y R5**.

**R4**

```cisco
enable
configure terminal
router rip
version 2
network 192.168.40.0
network 10.0.0.0
no auto-summary
end
write memory
```

`192.168.40.0` = **LAN de R4** (PC0 y PC1). `10.0.0.0` = **WAN de R4 hacia R3 y R1**.

**R5**

```cisco
enable
configure terminal
router rip
version 2
network 172.29.0.0
network 10.0.0.0
no auto-summary
end
write memory
```

`172.29.0.0` = **LAN de R5** (PC8 y servidor). `10.0.0.0` = **WAN de R5 hacia R3**.

### 3.4. Comprobaciones y tercera entrega

En R1:

```cisco
show ip protocols
show ip route
show ip route rip
```

Rutas nuevas con **`R`** y no con `S`. Observamos en nuestra práctica, por ejemplo, `10.10.5.0/30` (**WAN R3–R5**) aprendida desde R1 por **dos siguientes saltos de igual coste**: `10.10.1.2` (**R2**) y `10.10.4.1` (**R4**). Eso es normal en RIP.

> Si la consola muestra `--More--`, pulsar **barra espaciadora** para seguir leyendo la tabla y ver también las redes `192.168.x.x`.

```text
PC2: ping 192.168.20.10    (PC4, LAN R2)
PC2: ping 192.168.30.10    (PC6, LAN R3)
PC2: ping 192.168.40.10    (PC0, LAN R4)
PC2: ping 172.29.0.20      (Server0, LAN R5)
PC8: ping 192.168.10.10    (PC2, LAN R1)
```

**Archivo:** `DNI_P1_Dinamico.pkt`.

> **Erratas del guion:** en el ejemplo de RIP aparece `192.198.10.0` donde corresponde `192.168.10.0`, y se menciona `show ip route ospf` en la sección RIP. Para examinar RIP usamos `show ip route` o `show ip route rip`.

---

# 4. CUARTO ARCHIVO — `DNI_P1_ospf_area0.pkt`
## Apartado 4.3 del guion: OSPF con una única área

### 4.1. Preparación y objetivo

Abrir **`DNI_P1_IP.pkt`**, crear copia **`DNI_P1_ospf_area0.pkt`**.

**Qué hacemos:** OSPF es un protocolo dinámico de **estado de enlace**. Aprende la topología, establece **vecinos** con otros routers y calcula rutas según el **coste**. En esta entrega **todas las interfaces participantes están en el área 0**.

### 4.2. Comandos utilizados — significado ANTES de aplicarlos

| Comando | Qué significa |
|---|---|
| `router ospf 1` | Activa proceso OSPF número `1` (identificador **local**, no tiene que coincidir con el de otros routers). |
| `network RED WILDCARD area 0` | Selecciona las interfaces cuyas IP coinciden con `RED` y las asigna al **área 0**. |
| `0.0.0.255` | Wildcard de `/24`, para LAN `192.168.x.0`. |
| `0.0.0.3` | Wildcard de `/30`, para WAN `10.10.x.0`. |
| `0.0.255.255` | Wildcard de `/16`, para LAN del servidor `172.29.0.0`. |
| `show ip ospf neighbor` | Muestra vecinos; `FULL` indica adyacencia establecida. |
| `show ip ospf interface brief` | Muestra interfaces que participan y su área. |
| `show ip route ospf` | Muestra solo rutas aprendidas por OSPF (`O`; en varias áreas puede aparecer `O IA`). |
| `show ip ospf database` | Muestra la base de datos de estado de enlaces (LSDB). |
| `show ip protocols` | Muestra información del proceso de enrutamiento. |
| `end` / `write memory` | Sale y guarda la configuración. |

**Wildcard:** es la inversa bit a bit de la máscara: `/24` → `0.0.0.255`; `/30` → `0.0.0.3`; `/16` → `0.0.255.255`. **No cambia la IP ni su máscara real**: se usa en el comando `network` de OSPF.

### 4.3. Configuración de cada router: todo en área 0

**R1**

```cisco
enable
configure terminal
router ospf 1
network 192.168.10.0 0.0.0.255 area 0
network 10.10.1.0 0.0.0.3 area 0
network 10.10.4.0 0.0.0.3 area 0
end
write memory
```

- `192.168.10.0` = **LAN de R1** (PC2/PC3).
- `10.10.1.0` = **WAN R1–R2**.
- `10.10.4.0` = **WAN R1–R4**.

**R2**

```cisco
enable
configure terminal
router ospf 1
network 192.168.20.0 0.0.0.255 area 0
network 10.10.1.0 0.0.0.3 area 0
network 10.10.2.0 0.0.0.3 area 0
end
write memory
```

- `192.168.20.0` = **LAN de R2** (PC4/PC5).
- `10.10.1.0` = **WAN R2–R1**.
- `10.10.2.0` = **WAN R2–R3**.

**R3**

```cisco
enable
configure terminal
router ospf 1
network 192.168.30.0 0.0.0.255 area 0
network 10.10.2.0 0.0.0.3 area 0
network 10.10.3.0 0.0.0.3 area 0
network 10.10.5.0 0.0.0.3 area 0
end
write memory
```

- `192.168.30.0` = **LAN de R3** (PC6/PC7).
- `10.10.2.0` = **WAN R3–R2**.
- `10.10.3.0` = **WAN R3–R4**.
- `10.10.5.0` = **WAN R3–R5**; **aquí también está en área 0**.

**R4**

```cisco
enable
configure terminal
router ospf 1
network 192.168.40.0 0.0.0.255 area 0
network 10.10.3.0 0.0.0.3 area 0
network 10.10.4.0 0.0.0.3 area 0
end
write memory
```

- `192.168.40.0` = **LAN de R4** (PC0/PC1).
- `10.10.3.0` = **WAN R4–R3**.
- `10.10.4.0` = **WAN R4–R1**.

**R5**

```cisco
enable
configure terminal
router ospf 1
network 172.29.0.0 0.0.255.255 area 0
network 10.10.5.0 0.0.0.3 area 0
end
write memory
```

- `172.29.0.0` = **LAN de R5** (PC8/Server0).
- `10.10.5.0` = **WAN R5–R3**.

### 4.4. Comprobaciones y cuarta entrega

Desde **R3**:

```cisco
show ip ospf neighbor
show ip ospf interface brief
```

Se esperan **tres vecinos en `FULL`** (R2, R4 y R5) y todas las interfaces de R3 en **área 0**.

Desde **R1**:

```cisco
show ip route ospf
```

Buscar rutas `O` hacia LAN de **R2** (`192.168.20.0/24`), **R3** (`192.168.30.0/24`), **R4** (`192.168.40.0/24`) y **R5** (`172.29.0.0/16`).

En PC2:

```text
ping 192.168.20.10    (PC4, LAN de R2)
ping 192.168.30.10    (PC6, LAN de R3)
ping 192.168.40.10    (PC0, LAN de R4)
ping 172.29.0.20      (Server0, LAN de R5)
```

En PC8:

```text
ping 192.168.10.10    (PC2, LAN de R1)
```

**Archivo:** `DNI_P1_ospf_area0.pkt`.

---

# 5. QUINTO ARCHIVO — `DNI_P1_ospf_area0_1.pkt`
## Apartado 4.4 del guion: OSPF con dos áreas

### 5.1. Preparación y objetivo

Abrir **`DNI_P1_IP.pkt`**, crear copia **`DNI_P1_ospf_area0_1.pkt`**.

**Distribución indicada por el profesor:**

- **Área 0:** LAN y enlaces de **R1, R2, R3 y R4**.
- **Área 1:** enlace **R3–R5** (`10.10.5.0/30`) y LAN de **R5** (`172.29.0.0/16`).
- **R3 es ABR** (*Area Border Router*): tiene interfaces en las áreas 0 y 1 y permite intercambiar información de enrutamiento entre ambas.

### 5.2. Comandos utilizados — significado ANTES de aplicarlos

En esta entrega usamos **los mismos comandos OSPF de la sección 4**, con una diferencia clave:

| Comando o marcador | Qué significa en esta entrega |
|---|---|
| `router ospf 1` | Inicia el proceso OSPF. |
| `network RED WILDCARD area 0` | Coloca la interfaz correspondiente en el **área principal (backbone) 0**. |
| `network RED WILDCARD area 1` | Coloca la interfaz correspondiente en el **área 1**. |
| `show ip ospf neighbor` | Comprueba vecinos y estado `FULL`. |
| `show ip ospf interface brief` | Comprueba la pertenencia real de las interfaces a las áreas 0 o 1. |
| `show ip route ospf` | Comprueba rutas aprendidas por OSPF. |
| `O IA` | Ruta OSPF **entre áreas** (*inter-area*); por ejemplo, desde R1 hacia la LAN de R5. |
| `end` / `write memory` | Termina configuración y guarda. |

### 5.3. Configuración completa de los cinco routers

**R1 — Todas sus interfaces OSPF en área 0**

```cisco
enable
configure terminal
router ospf 1
network 192.168.10.0 0.0.0.255 area 0
network 10.10.1.0 0.0.0.3 area 0
network 10.10.4.0 0.0.0.3 area 0
end
write memory
```

- `192.168.10.0` (**LAN R1**, PC2/PC3), `10.10.1.0` (**WAN R1–R2**), `10.10.4.0` (**WAN R1–R4**): **área 0**.

**R2 — Todas sus interfaces OSPF en área 0**

```cisco
enable
configure terminal
router ospf 1
network 192.168.20.0 0.0.0.255 area 0
network 10.10.1.0 0.0.0.3 area 0
network 10.10.2.0 0.0.0.3 area 0
end
write memory
```

- `192.168.20.0` (**LAN R2**, PC4/PC5), `10.10.1.0` (**WAN R2–R1**), `10.10.2.0` (**WAN R2–R3**): **área 0**.

**R3 — Router fronterizo de área: tres redes en área 0 y una en área 1**

```cisco
enable
configure terminal
router ospf 1
network 192.168.30.0 0.0.0.255 area 0
network 10.10.2.0 0.0.0.3 area 0
network 10.10.3.0 0.0.0.3 area 0
network 10.10.5.0 0.0.0.3 area 1
end
write memory
```

- `192.168.30.0` (**LAN R3**, PC6/PC7): **área 0**.
- `10.10.2.0` (**WAN R3–R2**): **área 0**.
- `10.10.3.0` (**WAN R3–R4**): **área 0**.
- `10.10.5.0` (**WAN R3–R5**): **área 1**; esta es la diferencia esencial con el cuarto archivo.

**R4 — Todas sus interfaces OSPF en área 0**

```cisco
enable
configure terminal
router ospf 1
network 192.168.40.0 0.0.0.255 area 0
network 10.10.3.0 0.0.0.3 area 0
network 10.10.4.0 0.0.0.3 area 0
end
write memory
```

- `192.168.40.0` (**LAN R4**, PC0/PC1), `10.10.3.0` (**WAN R4–R3**), `10.10.4.0` (**WAN R4–R1**): **área 0**.

**R5 — Todas sus interfaces OSPF en área 1**

```cisco
enable
configure terminal
router ospf 1
network 172.29.0.0 0.0.255.255 area 1
network 10.10.5.0 0.0.0.3 area 1
end
write memory
```

- `172.29.0.0` (**LAN R5**, PC8 y Server0): **área 1**.
- `10.10.5.0` (**WAN R5–R3**): **área 1**.

### 5.4. Comprobaciones de OSPF entre áreas

En **R3**:

```cisco
show ip ospf neighbor
show ip ospf interface brief
```

**Resultado real que comprobamos:**

- Vecinos **R2** (`10.10.2.1`), **R4** (`10.10.3.2`) y **R5** (`10.10.5.2`) en estado **`FULL`**.
- `Gig0/0/0` (`192.168.30.1`, **LAN de R3**) → **área 0**.
- `Se0/1/0` (`10.10.2.2`, **R3 hacia R2**) → **área 0**.
- `Se0/1/1` (`10.10.3.1`, **R3 hacia R4**) → **área 0**.
- `Se0/2/0` (`10.10.5.1`, **R3 hacia R5**) → **área 1**.

En **R1**:

```cisco
show ip route ospf
```

Buscar la ruta `O IA 172.29.0.0/16` (**LAN de R5: PC8 y Server0**), que indica que R1 la aprende **desde otra área**.

Pruebas de ida y vuelta entre áreas:

```text
PC2: ping 172.29.0.20      (Server0, LAN de R5, área 1)
PC8: ping 192.168.10.10    (PC2, LAN de R1, área 0)
```

**Archivo:** `DNI_P1_ospf_area0_1.pkt`.

---

# 6. RESUMEN FINAL PARA REPASO RÁPIDO

## 6.1. Diferencia entre los métodos de enrutamiento

| Método | Cómo aprende rutas | Qué configuramos | Cómo aparece en `show ip route` |
|---|---|---|---|
| **Estático** | No aprende: el administrador introduce cada ruta. | `ip route` con red, máscara y vecino. | `S` |
| **RIP v2** | Intercambia rutas entre vecinos; métrica por saltos. | `router rip`, `version 2`, `network`, `no auto-summary`. | `R` |
| **OSPF área 0** | Intercambia información de estado de enlace y calcula rutas por coste. | `router ospf 1` y `network ... area 0`. | `O` |
| **OSPF áreas 0 y 1** | Igual que OSPF, pero intercambia también rutas entre áreas. | `network ... area 0` o `area 1`; R3 es ABR. | `O` / `O IA` |

## 6.2. Qué significa cada IP de uso frecuente

| Dirección | Identificación |
|---|---|
| `192.168.10.1` | **R1**, puerta de enlace de PC2 y PC3. |
| `192.168.20.1` | **R2**, puerta de enlace de PC4 y PC5. |
| `192.168.30.1` | **R3**, puerta de enlace de PC6 y PC7. |
| `192.168.40.1` | **R4**, puerta de enlace de PC0 y PC1. |
| `172.29.0.1` | **R5**, puerta de enlace de PC8 y servidor. |
| `172.29.0.20` | **Server0**, servidor situado en la LAN de R5. |
| `10.10.1.1` / `10.10.1.2` | Extremos **R1 / R2**. |
| `10.10.2.1` / `10.10.2.2` | Extremos **R2 / R3**. |
| `10.10.3.1` / `10.10.3.2` | Extremos **R3 / R4**. |
| `10.10.4.1` / `10.10.4.2` | Extremos **R4 / R1**. |
| `10.10.5.1` / `10.10.5.2` | Extremos **R3 / R5**. |

## 6.3. Orden para realizar una práctica similar desde cero

1. **Montar** routers, switches, PCs y servidor; instalar NIM-2T; conectar cables y puertos.
2. **Asignar IP** a PCs y servidor, con máscara y puerta de enlace.
3. **Configurar IP en interfaces de routers** (`interface`, `ip address`, `no shutdown`).
4. **Comprobar LAN y enlaces** con `show ip interface brief` y `ping` local.
5. **Guardar el archivo base** `DNI_P1_IP.pkt`.
6. **Crear copia para estático**, añadir rutas a LAN y WAN remotas, comprobar `S` y `ping`, guardar.
7. **Volver al archivo base y crear copia para RIP**, configurar en cinco routers, comprobar `R` y `ping`, guardar.
8. **Volver al archivo base y crear copia para OSPF área 0**, configurar, comprobar vecinos `FULL`, rutas `O` y `ping`, guardar.
9. **Volver al archivo base y crear copia para OSPF áreas 0 y 1**, comprobar R3 como ABR, rutas `O IA` y `ping`, guardar.

## 6.4. Lista de las cinco entregas

- [x] `DNI_P1_IP.pkt`
- [x] `DNI_P1_Estatico.pkt`
- [x] `DNI_P1_Dinamico.pkt`
- [x] `DNI_P1_ospf_area0.pkt`
- [x] `DNI_P1_ospf_area0_1.pkt`

> **Nota de precisión:** esta guía reconstruye los comandos que usamos y las comprobaciones comunicadas durante la conversación. No se han inspeccionado directamente los cinco archivos `.pkt`; para una verificación independiente de sus contenidos habría que abrirlos en Packet Tracer. La última comprobación OSPF que vimos textualmente fue la de vecinos e interfaces en R3; posteriormente confirmaste que las pruebas finales estaban realizadas.

**Fuente principal:** guion proporcionado, *Práctica 1. Enrutamiento estático y dinámico*, Fundamentos de Redes (10 páginas), complementado con la configuración concreta realizada durante esta conversación.
