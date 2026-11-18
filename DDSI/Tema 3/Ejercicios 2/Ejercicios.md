## Enunciado

Sea R una relación de esquema **{A, B, C, D, E}**, con un conjunto CK de claves candidatas formado por una sola clave **{E}** y que verifica el siguiente conjunto de dependencias funcionales:

**F = {E → C, C → D, C → A, ED → B}**

Se pide:

**a)** Obtener el recubrimiento minimal del conjunto de dependencias.

( Todos tienen un valor solo a la derecha.)
E -> C
C -> D
C -> A
ED -> B

Ahora vemos si podemos simplificar ED -> B

E+ = { E } 

Aplicamos E -> C por lo que E+ = { E,C }

Aplicamos C->D y C->A por lo que E+ = {E,C,D,A}

Aplicamos ED -> B porque tenemos E y D ---> E+ = {E,C,D,A,B}

Por lo que podemos eliminar D. ( E -> B )




**b)** Utilizar dicho recubrimiento minimal para determinar si la relación está en **Forma Normal de Boyce y Codd (FNBC)**, asumiendo que está en segunda y tercera forma normal sin comprobarlo.
Si no está en Forma Normal de Boyce y Codd, realizar una descomposición hasta que todas las relaciones que integran la descomposición estén en dicha forma normal.


Una relación está en Forma Normal de Boyce-Codd si, para toda dependencia funcional no trivial ( X -> Y )

En nuestro ejemplo E->C y E->B son superclaves. ( C no es superclave porque no puede llegar ni a E ni B ->  C+ = {C,D,A} )

NO está en Forma Normal BC


Ahora descomponemos: podemos agrupar C -> AD	R1 = X ∪ Y	/	R2 = R - ( Y-X )

X = C / Y = AD

R1 = {C,A,D}
R2 = {E,C,B}

R1 y R2 están en forma normal de BC






=============================================================================================


Se han propuesto 2 claves candidatas A y CD para una relación R de esquema {A,B,C,D}
F = {A->B, C->B, BD->A,A->D,B->C} 		Son A y CD claves?, son las únicas?


A+ = {A} -> {A,B,D} -> {A,B,C,D}

CD+ = {A,B,C,D}
	C+ = {C,B} 	-> NO
	D+ = {D} 	-> NO

Ninguno determina toda la relacion, por lo que CD es minima y además candidata.

Pero falta una última, BD+ = {B,D} -> {B,D,A,C}
							B+ = {B,C}
							D+ = {D}
