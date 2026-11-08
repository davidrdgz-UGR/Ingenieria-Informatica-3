
## Práctica 1 — Enrutamiento estático y dinámico | FR

### 1 - Preparación del entorno de red

En primer lugar, hemos creado la topología de red en Cisco Packet Tracer, siguiendo el esquema proporcionado en el guion de la práctica con los siguientes dispositivos:

- 5 Routers Cisco 4331
- 5 Switches Cisco 2960
- 9 Equipos
- 1 Servidor

Hemos realizado las conexiones entre los dispositivos utilizando:

- **Cable de cobre directo:** para conectar los ordenadores y el servidor con los switches, y estos con los routers.
- **Cable serie:** para conectar los routers entre sí, instalando previamente los módulos NIM-2T necesarios.
- **Cable de consola:** para acceder a la configuración de los routers desde los ordenadores.

### 2 - Direccionamiento IP

`Archivo: 45123750G_P1_IP.pkt`

Primero, hemos asignado las direcciones IP, máscaras de subred y puertas de enlace a los ordenadores y al servidor mediante la herramienta IP Configuration de Packet Tracer.

Después, hemos accedido a la terminal de cada router para configurar sus interfaces GigabitEthernet y Serial, asignándoles las direcciones correspondientes y activándolas mediante `no shutdown`.

**Comandos utilizados:**

- `enable`: acceder al modo privilegiado.
- `configure terminal`: entrar en el modo de configuración.
- `interface gigabitEthernet 0/0/0`: seleccionar la interfaz Ethernet.
- `interface serial 0/1/0`: seleccionar una interfaz serie.
- `ip address <IP> <máscara>`: asignar una dirección IP a la interfaz.
- `no shutdown`: activar la interfaz.
- `exit` / `end`: salir del modo de configuración.
- `write memory`: guardar la configuración.

### 3 - Enrutamiento estático

`Archivo: 45123750G_P1_Estatico.pkt`

Partiendo del archivo anterior, hemos configurado manualmente las rutas necesarias para permitir la comunicación entre las distintas redes.

Para ello, hemos utilizado el comando `ip route`, indicando en cada router las redes de destino y las direcciones IP de los siguientes saltos.

Finalmente, hemos añadido las rutas necesarias para alcanzar las redes WAN que no estaban conectadas directamente.

**Comandos utilizados:**

- `enable`: acceder al modo privilegiado.
- `configure terminal`: entrar en el modo de configuración.
- `ip route <red> <máscara> <siguiente_salto>`: añadir una ruta estática hacia una red de destino.
- `end`: salir del modo de configuración.
- `write memory`: guardar la configuración.

### 4 - Enrutamiento dinámico RIP

`Archivo: 45123750G_P1_Dinamico.pkt`

Partiendo del archivo original de direccionamiento IP, hemos configurado el protocolo RIP versión 2 en los cinco routers.

Hemos indicado las redes conectadas directamente a cada router para que puedan intercambiar información y aprender automáticamente las rutas hacia las demás redes.

También hemos desactivado el resumen automático de rutas.

**Comandos utilizados:**

- `enable`: acceder al modo privilegiado.
- `configure terminal`: entrar en el modo de configuración.
- `router rip`: activar el protocolo RIP.
- `version 2`: utilizar RIP versión 2.
- `network <red>`: indicar las redes que participan en RIP.
- `no auto-summary`: desactivar el resumen automático de rutas.
- `end`: salir del modo de configuración.
- `write memory`: guardar la configuración.

### 5 - Enrutamiento dinámico OSPF con una única área

`Archivo: 45123750G_P1_OSPF.pkt`

Partiendo nuevamente del archivo original, hemos configurado el protocolo OSPF en los cinco routers, utilizando una única área denominada **área 0**.

Para ello, hemos indicado las redes de cada router junto con sus máscaras wildcard, permitiendo que OSPF intercambie información y calcule las rutas entre las distintas redes.

**Comandos utilizados:**

- `enable`: acceder al modo privilegiado.
- `configure terminal`: entrar en el modo de configuración.
- `router ospf 1`: iniciar el proceso OSPF con identificador 1.
- `network <red> <wildcard> area 0`: añadir una red al área 0.
- `end`: salir del modo de configuración.
- `write memory`: guardar la configuración.

### 6 - Enrutamiento dinámico OSPF con varias áreas

`Archivo: 45123750G_P1_OSPF_area0-1.pkt`

Por último, hemos configurado OSPF dividiendo la topología en dos áreas:

- **Área 0:** formada por los routers R1, R2, R3 y R4.
- **Área 1:** formada por el enlace entre R3 y R5 y la red del servidor.

Hemos asignado las redes a sus respectivas áreas, utilizando R3 como router fronterizo (ABR) para permitir la comunicación entre ambas.

**Comandos utilizados:**

- `enable`: acceder al modo privilegiado.
- `configure terminal`: entrar en el modo de configuración.
- `router ospf 1`: iniciar el proceso OSPF.
- `network <red> <wildcard> area 0`: asignar una red al área 0.
- `network <red> <wildcard> area 1`: asignar una red al área 1.
- `end`: salir del modo de configuración.
- `write memory`: guardar la configuración.
