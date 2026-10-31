# Ingeniería de Servidores — Práctica 2, Lección 1

## Guía de procedimientos: red, SSH y cortafuegos

**Material de referencia:** `P2-L1.pdf` (52 páginas), *Configuración de red y SSH*, y guion general `ISE-P2-InstServ.pdf` (8 páginas).  
**Alcance:** pasos trabajados en la Lección 1: red, instalación y prueba de SSH, bloqueo del acceso directo de `root`, cambio de puerto, cortafuegos, diferencias entre sistemas y comprobaciones.  
**Sistemas contemplados:** Debian 13, Ubuntu Server y AlmaLinux 10 (algunos detalles dependen de la versión y de la instalación concreta).

> **Cómo leer esta guía:** `SERVIDOR` es la máquina a la que queremos conectarnos; `CLIENTE`, la máquina desde la que lanzamos `ssh`. Cualquier Linux puede desempeñar ambos papeles si tiene instalados los paquetes apropiados. Sustituye `IP_SERVIDOR`, `IP_CLIENTE`, `USUARIO` y `PUERTO` por los valores reales. En nuestras pruebas el usuario era `davidra` y elegimos el puerto **2222**; en el ejemplo del profesor figura **22022**. Usa **uno solo** en toda tu configuración.

---

## 1. Preparación y comprobaciones iniciales

### 1.1. Identificar sistema y dirección IP (en ambas máquinas)

```bash
cat /etc/os-release       # Distribución y versión
hostname                 # Nombre del equipo
whoami                   # Usuario actual
ip -br addr              # Interfaces e IP de forma abreviada
ip route                 # Rutas, incluida la salida por defecto a Internet
```

- La dirección `127.0.0.1` es *localhost*: conecta a **la propia máquina**, no a la otra.
- Que dos máquinas muestren `10.0.3.15` **no prueba** que se vean: podrían estar en redes NAT independientes.
- Antes de conectar por SSH hay que conocer una **IP del servidor alcanzable desde el cliente**.

### 1.2. Red de VirtualBox: qué muestra el profesor

El `P2-L1.pdf`, págs. 7–15, propone **Adaptador 1: NAT**, para Internet, y **Adaptador 2: Solo-anfitrión**, para comunicar máquinas mediante `vboxnet0`. Sus IP de ejemplo son Debian `192.168.56.105/24` y AlmaLinux `192.168.56.110/24`. Son ejemplos, **no** direcciones obligatorias.

También se puede usar **NAT + Red interna**, con ambas máquinas en la misma red interna e IP propias. Esta fue nuestra alternativa de laboratorio; **no es la configuración ilustrada en el PDF del profesor**. Un modo puente puede funcionar, pero depende de la red física y expone las máquinas a ella.

Comprobación básica desde el cliente:

```bash
ping -c 4 IP_SERVIDOR  # Prueba conectividad ICMP; puede fallar si ICMP está bloqueado
```

**Si no hay comunicación:** revisa los adaptadores de VirtualBox, que ambas máquinas estén en la misma red de laboratorio, IP/máscara y reglas de cortafuegos. No reinstales SSH para resolver una red sin conectividad.

### 1.3. Configuración manual que enseña el profesor (opcional)

Para una dirección **temporal** en la interfaz de laboratorio, según las págs. 13 y 15:

```bash
# Debian: SOLO si su interfaz de laboratorio es enp0s8
sudo ip addr add 192.168.56.105/24 dev enp0s8
sudo ip link set enp0s8 up

# AlmaLinux: SOLO si su interfaz de laboratorio es enp0s8
sudo ip addr add 192.168.56.110/24 dev enp0s8
sudo ip link set enp0s8 up
```

Estas IP no persisten tras reiniciar. **No ejecutes ambas configuraciones en la misma máquina** ni añadas direcciones si ya tienes una configuración correcta. El PDF también describe métodos persistentes: **Netplan + systemd-networkd** en Debian 13 (págs. 17–20) y **NetworkManager/nmcli** en AlmaLinux 10 (págs. 22–24). La configuración exacta depende de qué gestor tenga realmente tu instalación; en Ubuntu Server suele utilizarse Netplan. Evita que dos gestores controlen simultáneamente la misma interfaz.

---

## 2. Instalar SSH: cliente y servidor son distintos

**`ssh`** = programa cliente para **iniciar** conexiones.  
**`sshd`** = servicio servidor para **aceptarlas**.

| Distribución | Instalar CLIENTE si falta | Instalar SERVIDOR si falta | Nombre habitual del servicio |
|---|---|---|---|
| Debian / Ubuntu Server | `sudo apt update && sudo apt install -y openssh-client` | `sudo apt update && sudo apt install -y openssh-server` | `ssh` (en algunos sistemas también se admite `sshd`) |
| AlmaLinux | `sudo dnf install -y openssh-clients` | `sudo dnf install -y openssh-server` | `sshd` |

### 2.1. Antes de instalar: comprobar lo que ya existe

```bash
ssh -V                   # Versión del cliente; si responde, el cliente está disponible
command -v ssh           # Ruta del ejecutable ssh, si existe
command -v sshd          # Ruta de sshd si está en el PATH actual
```

Para confirmar **paquetes**:

```bash
# Debian / Ubuntu
 dpkg -l openssh-client openssh-server

# AlmaLinux
 rpm -q openssh-clients openssh-server
```

> No hay que instalar de nuevo un paquete que ya está instalado. El PDF explica que en AlmaLinux normalmente vienen cliente y servidor, pero conviene **comprobarlo**.

### 2.2. Iniciar y habilitar el servicio (EN EL SERVIDOR)

```bash
# Debian / Ubuntu Server
sudo systemctl enable --now ssh
sudo systemctl status ssh --no-pager

# AlmaLinux
sudo systemctl enable --now sshd
sudo systemctl status sshd --no-pager
```

- `start`: arranca ahora.
- `enable`: permite que arranque automáticamente en futuros reinicios.
- `enable --now`: realiza ambas operaciones.
- `status`: debe mostrar `active (running)`.

Si quieres averiguar qué unidad existe:

```bash
systemctl list-unit-files 'ssh*'
```

### 2.3. Primera conexión

En el **CLIENTE** (si el servidor todavía escucha en el puerto 22):

```bash
ssh USUARIO@IP_SERVIDOR
```

En nuestra práctica: `USUARIO` es `davidra`. Verifica la huella del servidor antes de aceptarla en el primer acceso. Una vez dentro:

```bash
whoami            # Usuario remoto
hostname          # Equipo en el que estás
who               # Sesiones abiertas (aparece en la pág. 34 del PDF)
exit              # Volver al equipo cliente
```

**Conexión en los dos sentidos:** si quieres conectar **AlmaLinux → Debian**, Debian también necesita **openssh-server** instalado y en marcha. Tener solo `openssh-client` en Debian no permite recibir conexiones.

---

## 3. Proteger SSH: impedir acceso remoto directo de root

El profesor lo hace en la pág. 37.

**EN EL SERVIDOR**, antes de editar, conserva una sesión de consola de VirtualBox accesible y crea copia:

```bash
sudo cp -a /etc/ssh/sshd_config /etc/ssh/sshd_config.bak
sudo nano /etc/ssh/sshd_config    # O sudo vi ... si nano no está instalado
```

Asegúrate de que exista esta línea **sin `#` delante**:

```text
PermitRootLogin no
```

- `#PermitRootLogin no` es un comentario, no aplica la directiva.
- `PermitRootLogin no` prohíbe iniciar sesión **por SSH como root**; **no** bloquea todos los accesos desde otras máquinas ni impide usar `sudo` con un usuario autorizado.

Si el escenario general exige autorizar solo a un usuario, añadimos (adaptación a nuestro usuario; **no es una línea desarrollada en las diapositivas P2-L1**):

```text
AllowUsers davidra
```

**Antes de reiniciar, comprobar:**

```bash
sudo sshd -t
sudo sshd -T | grep -E '^(permitrootlogin|allowusers) '
```

`sshd -t` sin salida suele indicar que la sintaxis es válida; **no garantiza** por sí solo que el servicio pueda arrancar ni que el puerto esté permitido por SELinux.

Reiniciar **solo si es correcto**:

```bash
# Debian / Ubuntu
sudo systemctl restart ssh

# AlmaLinux
sudo systemctl restart sshd
```

Desde el cliente, probar el usuario normal y el usuario `root` por el puerto configurado (ver §4). También puedes verificar `PermitRootLogin no` con `sshd -T` en el servidor.

**Importante:** revisa las directivas vigentes y los posibles archivos incluidos mediante `Include` (por ejemplo `/etc/ssh/sshd_config.d/`). No añadas copias contradictorias de `Port` o `PermitRootLogin`.

---

## 4. Cambiar el puerto SSH del 22 a otro

El profesor utiliza **22022** (págs. 38–45). Nosotros usamos **2222**; aquí mantenemos ese valor para reproducir lo ya trabajado. Sustitúyelo si vas a seguir exactamente el ejemplo del vídeo.

### 4.1. Comprobar el puerto actual

**EN EL SERVIDOR:**

```bash
sudo ss -tuln                     # Puertos TCP/UDP a la escucha
sudo sshd -T | grep '^port '      # Puerto efectivo configurado para sshd
```

### 4.2. Modificar `sshd_config`

```bash
sudo nano /etc/ssh/sshd_config
```

Cambiar `#Port 22` o `Port 22` por:

```text
Port 2222
```

Guardar y validar **antes** de reiniciar:

```bash
sudo sshd -t
sudo sshd -T | grep '^port '
```

**No reinicies todavía** si SELinux o el cortafuegos no están preparados para el puerto nuevo.

### 4.3. SELinux en AlmaLinux (no omitir si está Enforcing)

En AlmaLinux:

```bash
getenforce
sestatus
```

Si está `Enforcing`:

```bash
sudo dnf install -y policycoreutils-python-utils
sudo semanage port -l | grep ssh_port_t
```

Si **2222** no está autorizado ni asignado a otro tipo SELinux:

```bash
sudo semanage port -a -t ssh_port_t -p tcp 2222
```

Volver a comprobar:

```bash
sudo semanage port -l | grep ssh_port_t
```

**Error que nos apareció:** `SELinux policy is not managed or store cannot be accessed`. Con `Enforcing` activo, **no debe ignorarse**. Para diagnosticar sin desactivar SELinux:

```bash
sestatus
rpm -q policycoreutils-python-utils selinux-policy-targeted
sudo semanage port -l | grep ssh_port_t
```

Si faltan paquetes, instalarlos; si persiste, revisar los errores del almacén/políticas antes de reiniciar `sshd`. No podemos dar por reparado ese error sin una comprobación satisfactoria. El PDF del profesor dedica las págs. 43–44 a `semanage`.

### 4.4. Cortafuegos: permitir el puerto nuevo **antes del reinicio**

**AlmaLinux — firewalld:**

```bash
sudo systemctl status firewalld
sudo firewall-cmd --get-active-zones
sudo firewall-cmd --permanent --add-port=2222/tcp
sudo firewall-cmd --reload
sudo firewall-cmd --list-ports
```

Comprueba que la regla se añadió a la **zona que utiliza la interfaz de red entrante**. `--list-ports` consulta la zona predeterminada, que no siempre es la activa para esa interfaz; en ese caso especifica `--zone=ZONA`.

**Debian / Ubuntu Server — UFW (solo si esa máquina es el servidor al que quieres acceder por ese puerto):**

```bash
sudo apt install -y ufw        # Si aún no está instalado
sudo ufw status
sudo ufw allow 2222/tcp        # Antes de activar el firewall
sudo ufw enable
sudo ufw status verbose
```

Si Debian o Ubuntu siguen escuchando en **22**, permite `22/tcp` en lugar de `2222/tcp`. **Cada máquina configura su propio cortafuegos:** abrir 2222 en Debian no abre 2222 en AlmaLinux. El profesor muestra UFW para Debian (pág. 50), y `firewall-cmd` para AlmaLinux (pág. 45).

**No actives UFW y firewalld a la vez en el mismo servidor** para esta práctica.

### 4.5. Reiniciar, comprobar y conectarse

**En el SERVIDOR:**

```bash
sudo sshd -t
# Debian / Ubuntu: sudo systemctl restart ssh
# AlmaLinux:       sudo systemctl restart sshd
sudo ss -lntp | grep ':2222 '
```

**En el CLIENTE:**

```bash
ssh -p 2222 USUARIO@IP_SERVIDOR
```

Mantén la consola local o una sesión SSH válida disponible hasta que confirmes la conexión por el puerto nuevo. Solo después revisa si conviene retirar la regla que permitía el 22.

---

## 5. Comprobar el cortafuegos según distribución

| Necesidad | Debian / Ubuntu Server (UFW) | AlmaLinux (firewalld) |
|---|---|---|
| Comprobar estado | `sudo ufw status verbose` | `sudo systemctl status firewalld` |
| Mostrar reglas | `sudo ufw status numbered` | `sudo firewall-cmd --list-all` |
| Permitir TCP 2222 | `sudo ufw allow 2222/tcp` | `sudo firewall-cmd --permanent --add-port=2222/tcp` y `sudo firewall-cmd --reload` |
| Eliminar permiso TCP 2222 | `sudo ufw delete allow 2222/tcp` | `sudo firewall-cmd --permanent --remove-port=2222/tcp` y `sudo firewall-cmd --reload` |
| Activar cortafuegos | `sudo ufw enable` | `sudo systemctl enable --now firewalld` |

**Precaución:** no elimines una regla utilizada por tu propia sesión SSH. Si cambias el puerto, primero prueba desde otra terminal el acceso por el nuevo.

---

## 6. Diagnóstico de errores habituales

### A. `ssh localhost` → `Connection refused`

`localhost` = **la máquina donde ejecutas el comando**. Si en Debian solo has instalado `openssh-client`, **no hay ningún servidor escuchando** necesariamente.

```bash
# En Debian/Ubuntu que quieras probar consigo mismo
sudo apt install -y openssh-server
sudo systemctl enable --now ssh
sudo ss -lntp | grep ':22 '
ssh localhost
```

### B. Desde AlmaLinux a Debian → `Connection refused`

Verificar **en Debian**, que actúa como servidor para esa conexión:

```bash
sudo systemctl status ssh
sudo ss -lntp | grep ':22 '
sudo ufw status
```

Si falta servidor:

```bash
sudo apt install -y openssh-server
sudo systemctl enable --now ssh
```

Después, desde AlmaLinux:

```bash
ssh USUARIO_DEBIAN@IP_DEBIAN
```

Si Debian utiliza otro puerto, usar `-p PUERTO`. `Connection refused` suele indicar que se alcanza la IP pero no hay escucha aceptando la conexión en ese puerto (o se rechaza activamente).

### C. `ssh: command not found` (cliente ausente)

```bash
# AlmaLinux
sudo dnf install -y openssh-clients

# Debian / Ubuntu
sudo apt update && sudo apt install -y openssh-client
```

### D. `systemctl restart sshd` → error

```bash
sudo sshd -t                                      # Sintaxis
sudo systemctl status sshd -l --no-pager          # AlmaLinux
sudo journalctl -xeu sshd --no-pager              # AlmaLinux
sudo sshd -T | grep '^port '                      # Configuración efectiva
```

En Debian/Ubuntu, sustituir la unidad `sshd` por `ssh` si corresponde:

```bash
sudo systemctl status ssh -l --no-pager
sudo journalctl -xeu ssh --no-pager
```

Si falló tras cambiar el puerto en AlmaLinux y SELinux está `Enforcing`, comprobar **§4.3**. **UFW no arregla una denegación de SELinux.**

### E. `Permission denied` tras conectar

La red y el servicio pueden funcionar, pero el usuario o su método de autenticación son rechazados. Revisa el nombre de usuario, `AllowUsers`, `PermitRootLogin`, los métodos autorizados y los registros de `sshd`.

### F. `Connection timed out` / no route

Revisar IP del servidor, conectividad entre redes, adaptadores VirtualBox, rutas y cortafuegos. Es un problema distinto de la autenticación.

---

## 7. Comprobaciones finales (sin suponer resultados)

**En AlmaLinux (SERVIDOR):**

```bash
cat /etc/os-release
sudo systemctl status sshd --no-pager
sudo sshd -t
sudo sshd -T | grep -E '^(port|permitrootlogin|allowusers) '
sudo ss -lntp | grep ':2222 '
getenforce
sudo semanage port -l | grep ssh_port_t    # Si semanage está instalado
sudo firewall-cmd --list-all
```

**En Debian / Ubuntu (si recibe conexiones SSH):**

```bash
sudo systemctl status ssh --no-pager
sudo ss -lntp | grep ':22 '
sudo ufw status verbose
```

**Desde el CLIENTE:**

```bash
ssh -p 2222 davidra@IP_ALMALINUX
# Y, para probar el rechazo de root, tras verificar PermitRootLogin no:
ssh -v -p 2222 root@IP_ALMALINUX
```

**Para conexión inversa, desde AlmaLinux a Debian (si Debian escucha en 22):**

```bash
ssh davidra@IP_DEBIAN
```

No marques una comprobación como superada hasta ver el resultado real. Si SSH falla, revisa el apartado 6.

---

## 8. Diferencias entre Debian, Ubuntu Server y AlmaLinux

| Aspecto | Debian | Ubuntu Server | AlmaLinux |
|---|---|---|---|
| Paquetes | `apt` | `apt` | `dnf` |
| Cliente SSH | `openssh-client` | `openssh-client` | `openssh-clients` |
| Servidor SSH | `openssh-server` | `openssh-server` | `openssh-server` |
| Servicio habitual | `ssh` | `ssh` | `sshd` |
| Configuración de servidor SSH | `/etc/ssh/sshd_config` | Igual | Igual |
| Cortafuegos tratado en la práctica | `ufw` | `ufw` | `firewall-cmd` |
| Gestión de red habitual | Depende de instalación; el profesor muestra Netplan + systemd-networkd | Habitualmente Netplan | NetworkManager / `nmcli` |
| SELinux para puerto SSH no estándar | No es el procedimiento mostrado para Debian | No es el procedimiento mostrado para Ubuntu | Comprobar `getenforce` y registrar puerto con `semanage` si corresponde |

**Nota:** la inclusión de Ubuntu Server y los matices sobre unidades de servicio son adaptaciones útiles; el PDF específico desarrolla **Debian 13 y AlmaLinux 10**.

---

## 9. ¿Qué se hizo realmente y qué queda fuera?

**Trabajado en nuestra conversación:** instalación/verificación de cliente y servidor SSH; conexión en ambos sentidos; `PermitRootLogin no`; `AllowUsers davidra`; cambio de SSH al puerto 2222; reglas de `firewall-cmd` en AlmaLinux; UFW en Debian; revisión del error `semanage` con SELinux `Enforcing`; diagnósticos de `Connection refused` y reinicio fallido de `sshd`.

**No consta verificación final de todos los pasos:** especialmente el error de `semanage`/SELinux y el arranque de SSH tras el cambio de puerto. La guía permite comprobarlos, pero no afirma que ya estén reparados.

**Fuera del desarrollo del PDF específico P2-L1:** claves SSH, `nmap`, `screen`, `fail2ban` y `rkhunter`. Aparecen como requisitos/objetivos en el **guion general** `ISE-P2-InstServ.pdf` (págs. 5–7); no los incorporamos aquí como pasos ejecutados porque acordamos dejar esta lección tal como la llevábamos.

**Cierre:** después de una configuración comprobada y estable, guardar instantáneas de VirtualBox (recordatorio en la pág. 51 de `P2-L1.pdf`).

---

### Referencia documental

- *P2-L1.pdf*, págs. 7–24: configuración de red; págs. 26–34: SSH; págs. 36–45: endurecimiento, puerto, SELinux y cortafuegos; págs. 47–51: comparación y cierre.
- *ISE-P2-InstServ.pdf*, págs. 4–7: objetivos generales y escenario de la Lección 1.
