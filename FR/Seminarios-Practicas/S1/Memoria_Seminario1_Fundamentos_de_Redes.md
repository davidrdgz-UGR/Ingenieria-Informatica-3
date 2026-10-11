# Fundamentos de Redes (FR) — Seminario 1
## Memoria de la práctica y apuntes de repaso para el examen

**Práctica:** Configuración de un entorno de red con Cisco Packet Tracer  
**Material de referencia:** «Seminario 1. Configuración entorno red», curso 2026-2027 (PDF de 6 páginas).  
**Objetivo:** construir una topología con cuatro redes, asignar direcciones IPv4, configurar interfaces de routers y rutas estáticas, comprobar conectividad y estudiar el encapsulado de un paquete ICMP mediante PDU.

> **Cómo leer esta memoria:** «Realizado/comprobado» se refiere a lo que hicimos o verificamos durante nuestra conversación. «Según el guion» indica una instrucción del PDF. Algunos resultados finales no se documentaron uno por uno; no doy por vistos datos que no llegaste a mostrarme.

---

## 1. Topología que hemos montado

Dispositivos añadidos:
- **3 routers Cisco 4331:** `RX_1`, `RX_2`, `RX_3`.
- **4 switches Cisco 2960:** `Switch_LAN1`, `Switch_LAN2`, `Switch_Intranet`, `Switch_Extranet` (en el PDF aparece una pequeña errata en el nombre de Extranet).
- **4 ordenadores:** `PC_1`, `PC_2`, `PC_3`, `PC_4`.
- **1 servidor:** `ServidorWEB`.

Esquema lógico (no representa puertos concretos de switch):

```text
PC_1 (33.1.1.2) ─┐
                 ├── Switch_LAN1 ── RX_1 (33.1.1.1 / 172.16.1.1) ──┐
PC_2 (33.1.1.3) ─┘                                                   │
                                                                     │
PC_3 (33.1.2.2) ─┐                                                    ├── Switch_Intranet
                 ├── Switch_LAN2 ── RX_2 (33.1.2.1 / 172.16.1.2) ──┤    172.16.1.0/24
PC_4 (33.1.2.3) ─┘                                                   │
                                                                     │
ServidorWEB (10.0.0.10) ── Switch_Extranet ── RX_3 (10.0.0.1 / 172.16.1.3)
                                                                     └── (RX_3 también se conecta a Intranet)
```

**Cuatro redes diferentes:**

| Red | Identificador de red | Máscara | Equipos relevantes |
| --- | --- | --- | --- |
| LAN 1 | `33.1.1.0/24` | `255.255.255.0` | PC_1, PC_2 y RX_1 |
| LAN 2 | `33.1.2.0/24` | `255.255.255.0` | PC_3, PC_4 y RX_2 |
| Intranet | `172.16.1.0/24` | `255.255.255.0` | RX_1, RX_2 y RX_3 |
| Extranet | `10.0.0.0/24` | `255.255.255.0` | RX_3 y ServidorWEB |

**Idea clave:** un switch interconecta equipos de una misma red local a nivel de enlace; un router comunica redes IP distintas. Los tres routers se ven a través de la Intranet compartida. Las direcciones `33.1.x.x` son las que ha impuesto el ejercicio; no hay que sustituirlas por otras porque parezcan más habituales.

### 1.1 Cableado y puertos

El PDF pide **cables directos (Copper Straight-Through)** desde los equipos a sus switches.

| Router | Interfaz | Switch al que debe ir |
| --- | --- | --- |
| RX_1 | `GigabitEthernet0/0/0` | `Switch_LAN1` |
| RX_1 | `GigabitEthernet0/0/1` | `Switch_Intranet` |
| RX_2 | `GigabitEthernet0/0/0` | `Switch_LAN2` |
| RX_2 | `GigabitEthernet0/0/1` | `Switch_Intranet` |
| RX_3 | `GigabitEthernet0/0/0` | `Switch_Intranet` |
| RX_3 | `GigabitEthernet0/0/1` | `Switch_Extranet` |

Cada PC usa su interfaz Ethernet hacia su switch LAN; el servidor va a `Switch_Extranet`. **En el switch no es necesario utilizar siempre puertos Gigabit**: un router Gigabit también puede enlazarse a un puerto FastEthernet del 2960, a la velocidad negociada.

Consejo útil de Packet Tracer: **Options → Preferences → Interface → Always Show Port Labels in Logical Workspace** permite ver las etiquetas de los puertos sobre los cables. Cuando dudes, comprueba el puerto en ambos extremos; que un cable esté verde no demuestra que llegue al destino correcto.

---

## 2. Direccionamiento IP que configuramos

### 2.1 PCs y servidor

En un PC o servidor: **Desktop → IP Configuration → Static**.

| Equipo | Dirección IPv4 | Máscara | Default Gateway |
| --- | --- | --- | --- |
| PC_1 | `33.1.1.2` | `255.255.255.0` | `33.1.1.1` |
| PC_2 | `33.1.1.3` | `255.255.255.0` | `33.1.1.1` |
| PC_3 | `33.1.2.2` | `255.255.255.0` | `33.1.2.1` |
| PC_4 | `33.1.2.3` | `255.255.255.0` | `33.1.2.1` |
| ServidorWEB | `10.0.0.10` | `255.255.255.0` | `10.0.0.1` |

¿Qué representa cada campo?
- **IP:** identifica la interfaz en su red.
- **Máscara:** permite separar la parte de red de la parte de host.
- **Default Gateway:** dirección del router al que se envía el tráfico para otras redes, cuando no existe una ruta más específica.

**Por qué `/24`:** son 24 bits de red, equivalentes a `255.255.255.0`. Por ejemplo, `33.1.1.2/24` y `33.1.1.3/24` están en `33.1.1.0/24`; en cambio `33.1.2.2/24` está en otra red. Para una red `/24` habitual, `.0` es la dirección de red y `.255` la de difusión (broadcast), así que no se usan como direcciones ordinarias de host.

**Punto de examen:** PC_1 puede hablar con PC_2 a través del switch, sin atravesar RX_1. Para hablar con PC_3 o con ServidorWEB, tiene que enviar el tráfico hacia su puerta de enlace `33.1.1.1`.

### 2.2 Interfaces IP de los routers

| Router | Interfaz | Dirección IPv4 | Red |
| --- | --- | --- | --- |
| RX_1 | `GigabitEthernet0/0/0` | `33.1.1.1/24` | LAN 1 |
| RX_1 | `GigabitEthernet0/0/1` | `172.16.1.1/24` | Intranet |
| RX_2 | `GigabitEthernet0/0/0` | `33.1.2.1/24` | LAN 2 |
| RX_2 | `GigabitEthernet0/0/1` | `172.16.1.2/24` | Intranet |
| RX_3 | `GigabitEthernet0/0/0` | `172.16.1.3/24` | Intranet |
| RX_3 | `GigabitEthernet0/0/1` | `10.0.0.1/24` | Extranet |

**Importante:** en RX_3 los puertos no siguen el mismo orden LAN/Intranet de RX_1 y RX_2. Es fácil confundirse: su `0/0/0` va a Intranet y su `0/0/1` va a Extranet.

---

## 3. Cómo configuramos los routers mediante CLI

Los pasos y el significado de los comandos son iguales en los tres routers. Se entra en la pestaña **CLI** de cada dispositivo.

### 3.1 RX_1

```text
enable
configure terminal
interface gigabitEthernet 0/0/0
ip address 33.1.1.1 255.255.255.0
no shutdown
exit
interface gigabitEthernet 0/0/1
ip address 172.16.1.1 255.255.255.0
no shutdown
exit
end
write memory
```

### 3.2 RX_2

```text
enable
configure terminal
interface gigabitEthernet 0/0/0
ip address 33.1.2.1 255.255.255.0
no shutdown
exit
interface gigabitEthernet 0/0/1
ip address 172.16.1.2 255.255.255.0
no shutdown
exit
end
write memory
```

### 3.3 RX_3

```text
enable
configure terminal
interface gigabitEthernet 0/0/0
ip address 172.16.1.3 255.255.255.0
no shutdown
exit
interface gigabitEthernet 0/0/1
ip address 10.0.0.1 255.255.255.0
no shutdown
exit
end
write memory
```

### 3.4 Traducción de los comandos

| Comando | Para qué sirve |
| --- | --- |
| `enable` | Pasa del modo usuario `>` al modo privilegiado `#`. |
| `configure terminal` | Permite cambiar la configuración. |
| `interface gigabitEthernet 0/0/0` | Selecciona una interfaz física. |
| `ip address IP MASCARA` | Asigna la dirección IPv4 y su máscara. |
| `no shutdown` | Habilita administrativamente la interfaz. |
| `exit` | Sale del submodo actual. |
| `end` | Regresa directamente al modo privilegiado. |
| `write memory` | Guarda la configuración activa como configuración de arranque. |

**Verificación:**

```text
show ip interface brief
```

En nuestro caso, las dos interfaces usadas de cada router debían aparecer con `Status up` y `Protocol up`.
- `up / up`: interfaz habilitada y enlace operativo.
- `administratively down / down`: interfaz desactivada administrativamente (normal para `GigabitEthernet0/0/2`, que no usamos).
- `up / down` o `down / down`: hay que investigar la interfaz o el enlace; la causa depende del estado específico.

Durante el seminario revisamos las salidas de los tres routers y **las IP y los estados `up/up` de las interfaces utilizadas eran correctos**.

---

## 4. Enrutamiento estático

### 4.1 ¿Por qué hacía falta?

Al configurar las interfaces, cada router conoce las redes a las que está directamente conectado. Pero eso no le enseña automáticamente cómo llegar a las otras LAN. Añadimos rutas estáticas para indicarle el **siguiente salto**.

Sintaxis del PDF:

```text
ip route RED_DESTINO MASCARA SIGUIENTE_SALTO
```

Ejemplo: en RX_1, para llegar a `33.1.2.0/24`, el siguiente salto es RX_2 (`172.16.1.2`). No se pone la IP de PC_3 como siguiente salto: se pone la interfaz del router vecino por la que podremos alcanzar la red destino.

### 4.2 Rutas configuradas en RX_1

```text
enable
configure terminal
ip route 33.1.2.0 255.255.255.0 172.16.1.2
ip route 10.0.0.0 255.255.255.0 172.16.1.3
end
write memory
```

RX_1 ya conoce directamente `33.1.1.0/24` y `172.16.1.0/24`; necesitaba rutas para LAN 2 y Extranet.

### 4.3 Rutas configuradas en RX_2

```text
enable
configure terminal
ip route 33.1.1.0 255.255.255.0 172.16.1.1
ip route 10.0.0.0 255.255.255.0 172.16.1.3
end
write memory
```

RX_2 ya conoce directamente `33.1.2.0/24` y `172.16.1.0/24`; necesitaba rutas para LAN 1 y Extranet.

### 4.4 Rutas configuradas en RX_3

```text
enable
configure terminal
ip route 33.1.1.0 255.255.255.0 172.16.1.1
ip route 33.1.2.0 255.255.255.0 172.16.1.2
end
write memory
```

RX_3 ya conoce directamente `172.16.1.0/24` y `10.0.0.0/24`; necesitaba rutas para LAN 1 y LAN 2.

### 4.5 Ver la tabla de enrutamiento

```text
show ip route
show ip route static
```

Interpretación de las letras más relevantes:
- `C` = **Connected**: red conectada directamente.
- `L` = **Local**: dirección IP exacta de una interfaz del router (suele mostrarse como `/32`).
- `S` = **Static**: ruta añadida manualmente.

**Importante para el examen:** que exista una ruta de ida no basta; para que un `ping` funcione, la respuesta también tiene que poder regresar al origen. Por eso hubo que configurar rutas en todos los routers.

En nuestra práctica, me enviaste las tablas `show ip route` de **RX_1, RX_2 y RX_3** y las seis rutas estáticas coincidían con las necesarias. No había que añadir otras para esta topología.

Para eliminar una ruta estática concreta:

```text
configure terminal
no ip route RED_DESTINO MASCARA SIGUIENTE_SALTO
end
write memory
```

El PDF muestra también la eliminación indicando red y máscara. Incluir el siguiente salto exacto ayuda a identificar inequívocamente la ruta que quieres quitar.

---

## 5. Comprobaciones que hicimos

### 5.1 Herramientas básicas

Desde un PC: **Desktop → Command Prompt**.

```text
ipconfig
ping 33.1.1.1
ping 33.1.1.3
ping 33.1.2.2
ping 10.0.0.10
```

- `ipconfig`: comprueba la configuración IP del PC.
- `ping IP`: prueba si hay respuesta ICMP desde una dirección IP.
- Desde **Desktop → Web Browser**, `http://10.0.0.10` verifica además el acceso al servicio web, siempre que esté activado.

En **Realtime** también se puede usar **Add Simple PDU** (el sobre con `+`): primer clic en el origen, segundo clic en el destino. Esta herramienta permite lanzar una prueba de conectividad ICMP y consultar si es satisfactoria. Una PDU «Successful» demuestra conectividad para esa prueba; **no verifica todos los servicios ni todas las parejas de equipos**.

### 5.2 Incidencias reales durante la práctica

**Incidencia A: PC_3 → PC_1.**  
Realizamos un `ping` que respondió 3 de 4 veces (25 % de pérdida en ese intento). Un primer paquete sin respuesta puede ocurrir al descubrir direcciones MAC mediante ARP, pero no debe darse por hecho que esa fue la causa sin observar el proceso.

**Incidencia B: PC_1 → RX_3 y comunicación entre RX_1 y RX_3.**  
Al principio fallaban las pruebas hacia RX_3. Las tablas de enrutamiento estaban correctas, y las interfaces estaban `up/up`. Revisamos etiquetas de puertos, cableado, estado del switch y tablas ARP. Después de revisar/corregir las conexiones, confirmaste que **RX_1 y RX_3 ya se comunicaban**. No pudimos identificar de forma totalmente inequívoca, con los datos conservados, cuál fue el cambio exacto que lo solucionó.

**Incidencia C: PC_1 → PC_2.**  
También fallaba inicialmente. Son equipos de la misma LAN y no necesitan rutas estáticas para comunicarse. Propusimos revisar `ipconfig`, las IP y el switch LAN 1. Posteriormente confirmaste que **ya funcionaba**; no quedó documentada la causa concreta.

**Incidencia D: acceso al servidor.**  
Probaste desde PC_1 `ping 10.0.0.10` y `http://10.0.0.10`, y confirmaste que **ambas pruebas funcionaban**.

**Comprobaciones adicionales propuestas:**
- PC_1 → PC_3: `ping 33.1.2.2`.
- PC_2 → PC_4: `ping 33.1.2.3`.
- PC_3 → ServidorWEB: `ping 10.0.0.10`.
- PC_4 → ServidorWEB: `ping 10.0.0.10`.

Hablamos de probar **Add Simple PDU desde los cuatro PCs al servidor** y de comprobar PC_1 → PC_3; no conservamos una lista individual de los cinco resultados definitivos. Por eso no los marco como verificados uno a uno.

### 5.3 Qué enseñaron los problemas

1. **La red puede tener varias averías independientes.** Un fallo PC_1↔PC_2 no se arregla añadiendo rutas estáticas, porque los dos equipos están en la misma LAN.
2. **Un cable encendido en verde no basta.** Puede estar conectado a un puerto o switch que no correspondan con la IP asignada.
3. **La IP debe estar puesta en la interfaz física correcta.** Especial atención a los dos puertos de RX_3.
4. **Revisar antes de cambiar.** Consultar tablas y estados evita romper una configuración que ya funciona.
5. **Que aparezca `S` en la tabla es necesario para nuestras redes remotas, pero no prueba por sí solo el recorrido completo.** También hacen falta enlaces operativos, direcciones coherentes y ruta de retorno.

---

## 6. Diagnóstico de errores: método recomendado en el examen

Si un ping falla, **empieza por lo más cercano** y avanza hacia lo lejano:

**Paso 1 — Host.** Comprueba con `ipconfig` su IP, máscara y gateway. ¿Está en la misma subred que el destino? ¿Está repetida la IP en otro dispositivo?

**Paso 2 — LAN local.** Haz ping al gateway. Si dos PCs están en la misma LAN, pruébalos entre sí. Revisa sus cables, los puertos utilizados y que estén en la misma VLAN.

**Paso 3 — Interfaces.** En el router ejecuta `show ip interface brief`; revisa las IP y el estado `up/up`. Si una interfaz está administrativamente apagada, en su configuración necesita `no shutdown`.

**Paso 4 — Vecinos.** Desde el router, haz ping a la IP del router vecino en Intranet (por ejemplo, desde RX_1 a `172.16.1.3`). Como pertenecen a `172.16.1.0/24`, esa prueba **no requiere rutas estáticas**; si falla, revisa el enlace, los puertos, VLAN y resolución ARP.

**Paso 5 — Rutas.** Ejecuta `show ip route` o `show ip route static`: ¿existe ruta hacia la red destino? ¿El siguiente salto es el router correcto? ¿Existe ruta para volver?

**Paso 6 — Servidor.** Comprueba IP, máscara, gateway y conectividad con `10.0.0.1`. Si el ping responde pero la web no carga, revisa el servicio HTTP del servidor; son pruebas distintas.

**Comandos adicionales útiles:**

```text
# Router
show ip interface brief
show ip route
show ip route static
show arp
show running-config

# Switch
show interfaces status
show vlan brief
show mac address-table

# PC
ipconfig
ping DIRECCION_IP
```

En nuestro caso, el comando `show vlan brief` en `Switch_Intranet` mostraba los puertos en **VLAN 1**, y `show arp` permitió observar qué vecinos se habían resuelto. Ten presente que una tabla ARP sin una entrada no prueba, por sí sola, una desconexión: la entrada puede no haberse aprendido todavía o puede haber caducado.

Para avanzar por una salida CLI con `--More--`, utiliza la **barra espaciadora**.

---

## 7. Último apartado del guion: análisis de encapsulado con PDU

**Objetivo (página 6):** seguir un ICMP entre `PC_1` y `PC_3` y observar qué se mantiene y qué cambia en las capas del modelo OSI.

### 7.1 Procedimiento del guion

1. Cambiar de **Realtime** a **Simulation**.
2. En **Edit Filters**, seleccionar únicamente **ICMP**.
3. Usar **Add Simple PDU** (sobre con `+`).
4. Pulsar primero en `PC_1` (origen) y luego en `PC_3` (destino).
5. Examinar el sobre en `PC_1` antes de avanzar; consultar **OSI Model** y los detalles de la PDU de salida.
6. Avanzar con **Capture/Forward** hasta `RX_1` y comparar **Inbound PDU Details** con **Outbound PDU Details**.
7. Continuar el recorrido hasta `PC_3` y observar el **Echo Reply** de regreso.

Si hay sobres de pruebas antiguas que molestan, se pueden retirar en la lista de PDU del panel de simulación antes de crear la nueva.

**Nota sobre lo realizado:** preparamos el procedimiento y resolvimos cómo retirar una PDU antigua. Después me indicaste que habías entregado. No llegaste a mostrarme capturas de las pestañas OSI/PDU con sus MAC concretas, así que no afirmo que las hayamos anotado.

### 7.2 ¿Qué había que observar y por qué?

Recorrido de la solicitud (simplificado):

```text
PC_1 (33.1.1.2)
  → Switch_LAN1
  → RX_1 [LAN 33.1.1.1 | Intranet 172.16.1.1]
  → Switch_Intranet
  → RX_2 [Intranet 172.16.1.2 | LAN 33.1.2.1]
  → Switch_LAN2
  → PC_3 (33.1.2.2)
```

**En capa 3 (red):**
- IP de origen: **`33.1.1.2`**.
- IP de destino: **`33.1.2.2`**.
- Estas direcciones se mantienen a lo largo del recorrido de esta práctica (sin NAT). **No significa que todos los campos IP permanezcan iguales**: por ejemplo, el TTL disminuye en los saltos por router.

**En capa 2 (enlace):**
- De **PC_1 a RX_1**, la MAC destino de la trama es la de la interfaz LAN de RX_1, **no** la de PC_3.
- Al reenviar hacia Intranet, **RX_1 quita la trama de entrada y construye otra**: MAC origen de su interfaz de salida en Intranet y MAC destino de la interfaz de RX_2 en Intranet.
- Al reenviar hacia LAN 2, RX_2 vuelve a construir una trama apropiada para la red de PC_3.
- Los switches reenvían tramas a nivel de enlace; no realizan el mismo proceso de enrutamiento IP que los routers.
- **Las MAC reales dependen de la simulación**: para escribirlas con exactitud hay que leer `Inbound PDU Details` y `Outbound PDU Details` en Packet Tracer.

**En la respuesta ICMP:** PC_3 envía un **Echo Reply** de vuelta; entonces las IP origen/destino de la respuesta son `33.1.2.2` y `33.1.1.2`, respectivamente.

### 7.3 Plantilla para apuntar datos si lo repites

| Punto de observación | IP origen | IP destino | MAC origen | MAC destino |
| --- | --- | --- | --- | --- |
| PC_1, salida hacia RX_1 | `33.1.1.2` | `33.1.2.2` | Anotar en Packet Tracer | MAC LAN de RX_1 |
| RX_1, salida hacia RX_2 | `33.1.1.2` | `33.1.2.2` | MAC Intranet de RX_1 | MAC Intranet de RX_2 |
| RX_2, salida hacia PC_3 | `33.1.1.2` | `33.1.2.2` | MAC LAN de RX_2 | MAC de PC_3 |

**Frase importante para memorizar:** **la dirección IP indica el origen y destino final del paquete; la MAC identifica el origen y destino de la trama en el enlace actual.**

---

## 8. Chuleta exprés para el examen

### 8.1 Configuración mínima de interfaz

```text
enable
configure terminal
interface gigabitEthernet 0/0/0
ip address 192.168.1.1 255.255.255.0
no shutdown
end
write memory
```

*La IP `192.168.1.1` es un ejemplo genérico de estudio, no una dirección de nuestro seminario.*

### 8.2 Ruta estática mínima

```text
enable
configure terminal
ip route RED_DESTINO MASCARA IP_DEL_ROUTER_VECINO
end
write memory
```

### 8.3 Verificar

```text
show ip interface brief
show ip route
show ip route static
ping IP_DESTINO
```

### 8.4 Preguntas típicas y respuestas cortas

**¿Para qué sirve una puerta de enlace?** Para enviar tráfico hacia otras redes IP.

**¿Dos PCs con IP de la misma red `/24` necesitan router para hacerse ping?** No, si están correctamente conectados en la misma LAN/VLAN y pueden intercambiar tramas.

**¿Qué es una ruta estática?** Una entrada de la tabla de enrutamiento configurada manualmente para llegar a una red destino a través de un siguiente salto o una interfaz de salida.

**¿Por qué puede fallar el ping si la ruta de ida está bien?** Porque podría faltar la ruta de regreso, o existir un problema de host, enlace, interfaz u otro punto del recorrido.

**¿Qué significa `C` en `show ip route`?** Red conectada directamente.

**¿Qué significa `S`?** Ruta estática.

**¿Qué significa `L`?** Ruta local a la propia dirección de la interfaz.

**¿Qué hace `no shutdown`?** Habilita una interfaz que estaba administrativamente desactivada.

**¿Qué cambia en un router al reenviar un paquete IP por Ethernet?** La encapsulación de capa 2 y, entre otros cambios de capa 3, disminuye el TTL; las IP de los extremos de la comunicación se conservan en nuestro ejemplo sin NAT.

**¿Qué prueba `ping`?** Que el destino responde a solicitudes ICMP Echo, si están permitidas. No prueba por sí solo que la web HTTP funcione.

**¿Qué prueba abrir `http://10.0.0.10`?** Que el cliente puede comunicarse con el servidor web mediante HTTP, si el servicio responde.

**¿Qué significa que un paquete PDU figure como Successful?** Que la prueba configurada en Packet Tracer tuvo éxito; no que todos los servicios y caminos de la red estén validados.

### 8.5 Errores frecuentes a evitar

- Conectar el cable a una interfaz distinta de la que recibió la IP.
- Olvidar `no shutdown`.
- Confundir la **IP del host** con la **dirección de red** al crear `ip route`.
- Poner como siguiente salto una IP que no corresponde al vecino alcanzable.
- Olvidar la ruta de regreso.
- Poner un Gateway de otra LAN.
- Dar la misma IP a dos interfaces de la misma red.
- Intentar arreglar una falla entre dos PCs de la misma LAN añadiendo rutas estáticas.
- Confundir `up/up` con una garantía de conectividad extremo a extremo.
- Dar por fallida toda una red por el primer ping perdido: repetir y diagnosticar, sin atribuir automáticamente la causa a ARP.

---

## 9. Mini test de autoevaluación (sin mirar las soluciones)

1. ¿Qué redes conoce RX_1 sin añadir rutas estáticas?
2. Si PC_1 quiere hablar con PC_3, ¿a qué dirección IP envía inicialmente el tráfico como puerta de enlace?
3. ¿Por qué la MAC de destino de la trama que sale de PC_1 no es la MAC de PC_3?
4. ¿Qué ruta necesita RX_3 para poder devolver tráfico a PC_1?
5. ¿Qué comando muestra rápidamente las IP y el estado de las interfaces de un router?
6. ¿Qué significa `S 10.0.0.0/24 via 172.16.1.3` en RX_1?
7. Si PC_1 no llega a PC_2, ¿tiene sentido empezar comprobando las rutas estáticas?
8. ¿Qué diferencias hay entre `ping 10.0.0.10` y abrir `http://10.0.0.10`?
9. ¿Qué campo de capa 3 cambia típicamente al cruzar un router aunque las IP de los extremos permanezcan?
10. ¿Cuáles son las dos interfaces de RX_3 y a qué redes pertenecen?

### Soluciones

1. `33.1.1.0/24` (LAN 1) y `172.16.1.0/24` (Intranet).
2. A su gateway `33.1.1.1`, RX_1.
3. Porque PC_3 está en otra subred y PC_1 entrega la trama al siguiente salto local: la interfaz LAN de RX_1.
4. `ip route 33.1.1.0 255.255.255.0 172.16.1.1`.
5. `show ip interface brief`.
6. Que los paquetes destinados a `10.0.0.0/24` deben enviarse al siguiente salto `172.16.1.3` (RX_3).
7. No como primera medida: ambos PCs están en la misma LAN; revisar IP/máscara, cables, puertos y VLAN.
8. El primero comprueba respuesta ICMP; el segundo, acceso al servicio HTTP.
9. El **TTL**, que disminuye al atravesar routers.
10. `GigabitEthernet0/0/0 = 172.16.1.3/24` (Intranet); `GigabitEthernet0/0/1 = 10.0.0.1/24` (Extranet).

---

## 10. Conclusión personal y plan de repaso

En este seminario **montamos una red de cuatro segmentos**, configuramos direcciones IP y gateways, activamos seis interfaces Ethernet de tres routers y añadimos seis rutas estáticas. Durante las verificaciones encontramos fallos de conectividad y aprendimos a localizarlos sin modificar rutas que ya eran correctas. Finalmente confirmaste que **PC_1 accedía al servidor tanto con `ping` como mediante HTTP**, y que las comunicaciones que habían fallado entre RX_1/RX_3 y PC_1/PC_2 ya funcionaban. También preparamos el análisis de la PDU ICMP del apartado final.

**Recomendación para repasar:** intenta repetir la práctica una vez *sin copiar comandos*, consultando únicamente la tabla de IPs. Luego prueba a:

1. Configurar las interfaces de un router desde cero.
2. Deducir las rutas estáticas a partir de qué redes conoce directamente cada router.
3. Detectar un fallo introducido voluntariamente (gateway equivocado o interfaz apagada) usando `ipconfig`, `ping`, `show ip interface brief` y `show ip route`.
4. Seguir una PDU desde PC_1 hasta PC_3 y explicar por qué las IP de los extremos siguen siendo las mismas mientras cambian las MAC de enlace en los routers.

Si dominas esas cuatro tareas, habrás practicado los conceptos esenciales que aparecen en este guion.
