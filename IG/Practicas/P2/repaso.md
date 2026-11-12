REPASO PARA EXAMEN P2 | IG

LISTA

1-script_raiz.gd                        X 
2-Carga de modelos GLB y OBJ            X
3-Donut y mallas                        X
4-Sombreado por pixel y por vertices    X
5-Cubos de 8 y 24 vértices              X
6-Objeto por revolución                 X


- 1 | script_raiz.gd    ( PROFESOR )

`gdscript var dibujar_aristas : bool = false` -> activa/desactiva modo wireframe
  
`func _init(): RenderingServer.set_debug_generate_wireframes(true)` -> HABILITA mallas wirefrime al inicializar el objeto o script

`func _unhandled_key_input( key_event ): ` -> Aqui es donde detecta el input de una tecla, y dentro se hace la deteccion de la W.

DEBUG_DRAW_WIREFRAME es lo que dibuja realmente las aristas y el modo Wireframe  


- 2 | Carga de modelos GLB y OBJ   ( AÑADIDO )

GLB -> Formato para almacenar modelo 3D y sus recursos en un archivo único. ( Geometria, Materiales, Jerarquia de Nodos, Animaciones )
    - Formato Binario

OBJ -> Permite almacenar la geometria de un modelo 3D ( vertices (v) / Coordenadas de textura (vt) / Normales de vértice (vn) / Define caras mediante indices (f) )
    - Formato texto, No animaciones, Referencias a MTL.

MTL -> Contiene las definiciones de los materiales del OBJ.


- 3 | Donut y generacion de mallas   ( PROFESOR )

Utilizamos -> Utilidades.gd + donut.gd  ( PROCESO: Utilidades.generarDonut() -> Utilidades.calcNormales() -> ArrayMesh -> Material y sombreado )


**Donut.gd -> Construye la malla del donut.**

Creamos `vertices` y `triangulos`

`Utilidades.generarDonut( vertices, triangulos )` -> Rellena los arrays de vértices y triángulos necesarios para construir la geometría del donut.

`tablas[ Mesh.ARRAY_VERTEX ] = vertices` `tablas[ Mesh.ARRAY_INDEX  ] = triangulos` `tablas[ Mesh.ARRAY_NORMAL ] = normales`
    Con esto creamos una lista de arrays a la que añadimos todos los valores y despues creamos la Mesh, y finalmente le añadimos la superficie con tablas.
        (`ArrayMesh.new()`) -> `mesh.add_surface_from_arrays( Mesh.PRIMITIVE_TRIANGLES, tablas )` + Añadimos sus materiales, sombras, etc.

!!!!! Comentamos mesh_normales = `Algoritmos.generarSegmentosNormales( vertices, normales, 0.15, Color(...) )`
        Servía para representar visualmente las normales del donut, venia del archivo algoritmos-varios.gd

**Utilidades.gd -> Contiene `generarDonut()` y `calcNormales()`**

`normales` -> Almacena vectores para calcular la iluminacion

`Utilidades.generarDonut(...)` -> genera la geometria mediante 2 bucles i,j / añadiendo al array coordenadas con `.append(paramDonut( valores base ) )`

`Utilidades.calcNormales(vertices, triangulos)` -> Calcula las normales y nos la llevamos a donut.gd
    - 1. Comprueba parametros | 2. Inicia a 0 las normales. |  3. Suma a cada vertice las normales | 4. Las normaliza | 5 Las devuelve a utilidades en donut.gd

`GenTriToroidal()` -> Finalmente se ejecuta esta funcion creando los triangulos toroidales para el donut 



- 4 | Sombreado por pixel y por vertices   ( PROFESOR )

El sombreado determina cómo se calcula la iluminación de la superficie de un objeto 3D, teniendo en cuenta sus normales, las luces y el material.

Sombreado por vértice -> Se calcula en los vértices y despues se interpola entre ellos, es menos precios.
    `mat.shading_mode = BaseMaterial3D.SHADING_MODE_PER_VERTEX`

Sombreado por píxel -> La iluminacion se calcula por cada fragmento de superficie interpolando las normales, más preciso.
    `mat.shading_mode = BaseMaterial3D.SHADING_MODE_PER_PIXEL`


- 5 | Cubos de 8 y 24 vértices (aristas duras) ( AÑADIDO )

Ambos utilizan las funciones de Utilidades para calcular normales

Vertices    -> 8/24
Triangulos  -> 12/12
Vertices Compartidos -> Si / No
Normales    -> Promediadas / Una dirección por cara

Lo que conseguimos con más vertices es una iluminacion plana, con artistas duras ( marcando sus esquinas ) , mientras que con menos es algo más suavizada.

En ambos casos creamos un array con todos los vertices y sus conexiones, y otro array con los triangulos formados.

Para calcular las normales y crear el objeto seguimos el proceso anterior usado para el donut, con `calcNormales()` y `mesh.add_surface_from_arrays(Mesh.PRIMITIVE_TRIANGLES, tablas)`

Despues les añadimos los materiales y sombras y listo.


```gdscript

    ## Cubo 8 vertices
	var vertices := PackedVector3Array([
		Vector3(-0.5, -0.5, -0.5), # 0
		Vector3( 0.5, -0.5, -0.5), # 1
		Vector3( 0.5,  0.5, -0.5), # 2
		Vector3(-0.5,  0.5, -0.5), # 3
		Vector3(-0.5, -0.5,  0.5), # 4
		Vector3( 0.5, -0.5,  0.5), # 5
		Vector3( 0.5,  0.5,  0.5), # 6
		Vector3(-0.5,  0.5,  0.5)  # 7
	])

	var triangulos := PackedInt32Array([
		0, 2, 1,  0, 3, 2,
		1, 6, 5,  1, 2, 6,
		5, 7, 4,  5, 6, 7,
		4, 3, 0,  4, 7, 3,
		3, 6, 2,  3, 7, 6,
		4, 1, 5,  4, 0, 1
	])


    Cubo 24 vertices

    	var vertices := PackedVector3Array([
		# Cara frontal
		Vector3(-0.5, -0.5,  0.5),
		Vector3( 0.5, -0.5,  0.5),
		Vector3( 0.5,  0.5,  0.5),
		Vector3(-0.5,  0.5,  0.5),

		# Cara trasera
		Vector3( 0.5, -0.5, -0.5),
		Vector3(-0.5, -0.5, -0.5),
		Vector3(-0.5,  0.5, -0.5),
		Vector3( 0.5,  0.5, -0.5),

		# Cara izquierda
		Vector3(-0.5, -0.5, -0.5),
		Vector3(-0.5, -0.5,  0.5),
		Vector3(-0.5,  0.5,  0.5),
		Vector3(-0.5,  0.5, -0.5),

		# Cara derecha
		Vector3( 0.5, -0.5,  0.5),
		Vector3( 0.5, -0.5, -0.5),
		Vector3( 0.5,  0.5, -0.5),
		Vector3( 0.5,  0.5,  0.5),

		# Cara superior
		Vector3(-0.5,  0.5,  0.5),
		Vector3( 0.5,  0.5,  0.5),
		Vector3( 0.5,  0.5, -0.5),
		Vector3(-0.5,  0.5, -0.5),

		# Cara inferior
		Vector3(-0.5, -0.5, -0.5),
		Vector3( 0.5, -0.5, -0.5),
		Vector3( 0.5, -0.5,  0.5),
		Vector3(-0.5, -0.5,  0.5)
	])

	var triangulos := PackedInt32Array([
		0, 1, 2,   0, 2, 3,
		4, 5, 6,   4, 6, 7,
		8, 9, 10,  8, 10, 11,
		12, 13, 14, 12, 14, 15,
		16, 17, 18, 16, 18, 19,
		20, 21, 22, 20, 22, 23
	])

```


- 6 | Objeto por Revolución ( AÑADIDO )

revolucion.gd -> Contiene el algoritmo que genera los vertices y triangulos al girar alrededor del eje Y. Usando un objeto `Revolucion`
    Primero calcula los angulos de cada copia, despues giramos con `Vector3`


objeto_revolucion.gd -> Define el perfil, llama al algoritmo, calcula las normales y construye la ArrayMesh.
    Siguiendo los pasos anteriores, creando Arrays de vertices y Triangulos, calcNormales(), Tablas y Mesh, pero creando un objeto revolución con sus atributos y creando copias (32) alrededor del eje Y.

Define Perfil 2D y el numero de copias -> Revolucion.gd recibe el perfil, genera copias y gira alrededor de Y con sin() y cos() 
-> Guarda vertices en Vector3 y los conecta mediante los triangulos -> Recibe los arrays de vertices/triangulos y calcula normales 
-> Construye el ArrayMesh y aplica el material.

revolucion.gd sirve como clase, al crear el objeto en objeto_revolucion, el proceso de giro se hace automaticamente y queda guardado en sus atributos