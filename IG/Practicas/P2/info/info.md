## **PRACTICA 2 | IG**

En esta práctica se ha trabajado con la representación y generación de mallas 3D en Godot, partiendo de los conceptos básicos vistos en la práctica anterior. Se han cargado modelos externos en formatos GLB y OBJ, se ha añadido un modo de visualización wireframe y se ha estudiado el papel de las normales en la iluminación de los objetos.

También se han comparado distintos tipos de sombreado, se han analizado las diferencias entre mallas suaves y objetos con aristas reales, y se han generado objetos de forma procedural mediante código. Finalmente, se ha implementado un algoritmo de revolución de un perfil 2D alrededor del eje Y para construir una malla tridimensional y calcular sus normales.


- ( Los códigos dados por el profesor no los pondrémos en cada apartado )


### **Modo de visualización Wireframe**

Se añadió al nodo raíz de la escena el script script_raiz.gd, cuya función es permitir cambiar entre la visualización normal y el modo wireframe pulsando la tecla W.

El modo wireframe muestra únicamente las aristas de los triángulos que forman las mallas 3D. Esto resulta especialmente útil para analizar cómo están construidos los objetos y comprobar la distribución de sus triángulos durante la generación de geometría.

Al pulsar la tecla W, se alterna entre ambos estados y se cambia el modo de dibujo del Viewport.

![wireframe1](./img/cap1.png)
![wireframe2](./img/cap2.png)


### **Carga de modelos 3D externos**

Durante la práctica se importaron modelos externos en formatos GLB y OBJ para comprobar cómo trabaja Godot con distintos tipos de archivos 3D, y se han guardado dentro de la carpeta modelos3D y se han añadido como hijos de ObjetosP2 ( en godot ).

En la práctica se importaron modelos en formatos GLB y OBJ. El formato GLB permite incluir geometría, materiales y texturas en un único archivo, mientras que el formato OBJ suele necesitar también un archivo .mtl para definir sus materiales.

![obj-glb](./img/cap3.png)


### **Donut y cálculo de normales**

Se creó un objeto con forma de donut mediante una malla generada por código. 
Para ello se utilizan arrays de vértices y triángulos, a partir de los cuales se construye la geometría del objeto.

Después se calculan las normales de cada vértice mediante calcNormales(). 
Estas normales se obtienen a partir de las caras adyacentes y son necesarias para que la iluminación del objeto se calcule correctamente.

Finalmente, con los vértices, triángulos y normales se crea una ArrayMesh y se aplica un material al donut.

![donut](img/cap4.png)


### **Sombreado por píxel y por vértice**

Se compararon los dos tipos de sombreado disponibles en Godot utilizando el donut.

  

Con el sombreado por píxel (```BaseMaterial3D.SHADING_MODE_PER_PIXEL```), la iluminación se calcula en cada píxel de la superficie, obteniendo un resultado más suave y realista. 




En cambio, con el sombreado por vértice (```BaseMaterial3D.SHADING_MODE_PER_VERTEX```), la iluminación se calcula en los vértices y después se interpola sobre los triángulos, por lo que el resultado es menos preciso.
