### Análisis previo de los atributos
* **No aparecen en $F$ (Independientes):** $C$
* **Solo a la izquierda (LHS):** $F$
* **Solo a la derecha (RHS):** $E$
* **En ambos lados (Mixtos):** $A, B, D$

---

### 1 - RSI Independientes
Los atributos independientes son aquellos que no intervienen en ninguna dependencia funcional. Deben formar parte obligatoria de todas las claves candidatas, por lo que se extraen temporalmente para simplificar la búsqueda.
* **Atributo independiente:** $\{C\}$
* **$R_{si}$** = $\{A, B, D, E, F\}$
* **$F_{si}$** = $\{AD \rightarrow EB, F \rightarrow A, B \rightarrow A, AB \rightarrow D\}$

### 2 - RSIE Equivalentes
Buscamos si existen atributos equivalentes (que se implican mutuamente de forma directa o indirecta, ej. $X \rightarrow Y$ y $Y \rightarrow X$).
* Revisando $F_{si}$: Tenemos $B \rightarrow A$ y $F \rightarrow A$, pero ni $A \rightarrow B$ ni $A \rightarrow F$. No hay ciclos de equivalencia.
* **$R_{sie}$** = $R_{si} = \{A, B, D, E, F\}$
* **$F_{sie}$** = $F_{si}$

### 3 - Kp izquierda
Creamos la clave potencial ($K_p$) inicial con los atributos que **solo aparecen a la izquierda** de las dependencias funcionales.
* **$K_p$** = $\{F\}$

Calculamos el cierre de este atributo ($K_p^+$) en $F_{sie}$:
1. $K_p^+ = \{F\}$
2. Por la dependencia $F \rightarrow A$, añadimos $A \rightarrow \{F, A\}$
3. No se pueden aplicar más dependencias ($B \rightarrow A$ ya está contenida, y para las demás nos faltan componentes).
* **$K_p^+$** = $\{F, A\}$ *(Como no equivale a $R_{sie}$, no es clave por sí sola).*

### 4 - Kp izquierda y derecha
Combinamos nuestra $K_p$ con los atributos mixtos (izquierda y derecha) que no estén en el cierre previo ($B$ y $D$) para intentar completarlo.

* **Combinación con $B$:** $\{F, B\}$
    * Cierre $\{F, B\}^+ = \{F, B\}$
    * Por $F \rightarrow A \rightarrow \{F, B, A\}$
    * Por $AB \rightarrow D \rightarrow \{F, B, A, D\}$
    * Por $AD \rightarrow EB \rightarrow \{F, B, A, D, E\}$ (Alcanza todo $R_{sie}$).
* **Combinación con $D$:** $\{F, D\}$
    * Cierre $\{F, D\}^+ = \{F, D\}$
    * Por $F \rightarrow A \rightarrow \{F, D, A\}$
    * Por $AD \rightarrow EB \rightarrow \{F, D, A, E, B\}$ (Alcanza todo $R_{sie}$).

* **Claves candidatas de $R_{sie}$:** $\{\{F, B\}, \{F, D\}\}$

### 5 - CK Incorporar independientes
Reincorporamos el atributo independiente **$C$** (reservado en el paso 1) a las claves calculadas:
* **$CK_{si}$** = $\{\{B, C, F\}, \{C, D, F\}\}$

### 6 - CK deshacer equivalencias
Al no haber agrupado ni sustituido ningún atributo por equivalencia en el paso 2, este paso se mantiene idéntico.

---

### **Resultado Final**
Las **claves candidatas (CK)** de la relación $R$ son:
* **$CK_1 = \{B, C, F\}$**
* **$CK_2 = \{C, D, F\}$**


<br>
<br>
<br>

### **Conjunto Original de Dependencias Funcionales (F)**
$F = \{D \rightarrow B, AF \rightarrow C, DEC \rightarrow F, DF \rightarrow AB, B \rightarrow D, DE \rightarrow B, DB \rightarrow C\}$

---

### Paso 1: Descomposición de los lados derechos (LHS y RHS simples)
El primer paso consiste en transformar todas las dependencias funcionales para que tengan un **único atributo en su lado derecho**. La única dependencia que se descompone es $DF \rightarrow AB$.

* $D \rightarrow B$
* $AF \rightarrow C$
* $DEC \rightarrow F$
* $DF \rightarrow A$ *(Descompuesta)*
* $DF \rightarrow B$ *(Descompuesta)*
* $B \rightarrow D$
* $DE \rightarrow B$
* $DB \rightarrow C$

---

### Paso 2: Eliminación de atributos extraños (Lado izquierdo)
Analizamos las dependencias que tienen más de un atributo en su lado izquierdo para comprobar si alguno de ellos es redundante (extraño).

* **Para $AF \rightarrow C$:**
  * ¿Es $A$ extraño? Calculamos el cierre de $F$ sin esta dependencia: $F^+ = \{F, A, D, B, C\}$ (utilizando $F \rightarrow A$ de abajo). Como $C \in F^+$, $A$ podría parecer extraño, pero al evaluar el cierre de $F$ puro con el conjunto actual: $F^+ = \{F\}$. No contiene a $C$. $A$ **no** es extraño.
  * ¿Es $F$ extraño? Cierre de $A$: $A^+ = \{A\}$. No contiene a $C$. $F$ **no** es extraño.

* **Para $DEC \rightarrow F$:**
  * ¿Es $D$ extraño? Cierre de $EC$: $EC^+ = \{E, C\}$. No contiene a $F$.
  * ¿Es $E$ extraño? Cierre de $DC$: $DC^+ = \{D, C, B\}$. No contiene a $F$.
  * ¿Es $C$ extraño? Cierre de $DE$: $DE^+ = \{D, E, B, C\}$ (ya que $D \rightarrow B$ y $DB \rightarrow C$). Como el cierre de $DE$ **ya contiene a $C$**, el atributo $C$ es **extraño** en el lado izquierdo.
  * **Resultado:** $DEC \rightarrow F$ se simplifica a **$DE \rightarrow F$**.

* **Para $DF \rightarrow A$ y $DF \rightarrow B$:**
  * Los cierres individuales de $D$ y $F$ no alcanzan a cubrir al otro ni a los lados derechos correspondientes. Ninguno es extraño.

* **Para $DB \rightarrow C$:**
  * ¿Es $D$ extraño? Cierre de $B$: $B^+ = \{B, D, C\}$ (ya que $B \rightarrow D$ y luego $DB \rightarrow C$). Como $D \in B^+$, el atributo $D$ es **extraño**.
  * **Resultado:** $DB \rightarrow C$ se simplifica a **$B \rightarrow C$**.

#### Conjunto tras el Paso 2:
* $D \rightarrow B$
* $AF \rightarrow C$
* $DE \rightarrow F$ *(Simplificada)*
* $DF \rightarrow A$
* $DF \rightarrow B$
* $B \rightarrow D$
* $DE \rightarrow B$
* $B \rightarrow C$ *(Simplificada)*

---

### Paso 3: Eliminación de dependencias redundantes
Probamos cada dependencia una por una. Si al eliminarla, el lado izquierdo sigue siendo capaz de calcular el lado derecho mediante el resto de dependencias, la dependencia es redundante y se elimina definitivamente.

* **Probar $D \rightarrow B$:** Cierre de $D$ sin ella: $D^+ = \{D\}$. No contiene a $B$. **Se queda.**
* **Probar $AF \rightarrow C$:** Cierre de $AF$ sin ella: $AF^+ = \{A, F, D, B, C\}$ (por $DF \rightarrow B$, $B \rightarrow C$). Como $C \in AF^+$, la dependencia $AF \rightarrow C$ es **redundante**. ❌ *(Se elimina)*
* **Probar $DE \rightarrow F$:** Cierre de $DE$ sin ella: $DE^+ = \{D, E, B, C\}$. No contiene a $F$. **Se queda.**
* **Probar $DF \rightarrow A$:** Cierre de $DF$ sin ella: $DF^+ = \{D, F, B, C\}$. No contiene a $A$. **Se queda.**
* **Probar $DF \rightarrow B$:** Cierre de $DF$ sin ella: $DF^+ = \{D, F, A\}$. Como $D \rightarrow B$ está disponible, $DF^+ = \{D, F, A, B\}$. Como contiene a $B$, la dependencia $DF \rightarrow B$ es **redundante**. ❌ *(Se elimina)*
* **Probar $B \rightarrow D$:** Cierre de $B$ sin ella: $B^+ = \{B, C\}$. No contiene a $D$. **Se queda.**
* **Probar $DE \rightarrow B$:** Cierre de $DE$ sin ella: $DE^+ = \{D, E, F, A, B\}$ (por $DE \rightarrow F$, $DF \rightarrow A$, y luego $D \rightarrow B$). Como contiene a $B$, la dependencia $DE \rightarrow B$ es **redundante**. ❌ *(Se elimina)*
* **Probar $B \rightarrow C$:** Cierre de $B$ sin ella: $B^+ = \{B, D\}$. No contiene a $C$. **Se queda.**

---

### **Resultado Final (Recubrimiento Minimal)**
Agrupando de nuevo los lados derechos que tengan el mismo lado izquierdo, el conjunto minimal es:

* **$D \rightarrow B$**
* **$DE \rightarrow F$**
* **$DF \rightarrow A$**
* **$B \rightarrow D, C$** *(o escritas por separado: $B \rightarrow D$ y $B \rightarrow C$)*
