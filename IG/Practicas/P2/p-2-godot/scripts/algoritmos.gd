extends Node

## genera un objeto MeshInstance con segmentos de líneas en las normales, no sombreados, 
## con una longitud y un color todos ellos
func generarSegmentosNormales( verts, norms : PackedVector3Array, lon : float, color : Color ) -> MeshInstance3D : 
	
	assert( verts.size() > 0 , "la tabla de vértices está vacía")
	assert( norms.size() == verts.size() , "la tabla de normales no tiene el tamaño de la de vértices")
	print("generarSegmentosNormales, verts.size() == ",verts.size())
	
	## crear array con coordenadas de los extremos de los segmentos 
	var posic := PackedVector3Array([]) 
	for iv in verts.size():
		posic.append( verts[iv] )
		posic.append( verts[iv] + lon * norms[iv].normalized() )
		
	print("generarSegmentosNormales, posic.size() == ",posic.size())
	
	## crear las tablas de la malla
	var tablas : Array = []  
	tablas.resize( Mesh.ARRAY_MAX )
	tablas[ Mesh.ARRAY_VERTEX ] = posic
	
	## crear el MeshInstance 'mi' con su material 'mat'
	var mat := StandardMaterial3D.new()
	mat.albedo_color = color
	mat.shading_mode = BaseMaterial3D.SHADING_MODE_UNSHADED
	
	var mi := MeshInstance3D.new()
	mi.mesh = ArrayMesh.new()
	mi.mesh.add_surface_from_arrays( Mesh.PRIMITIVE_LINES, tablas )
	mi.material_override = mat
	 
	return mi 
	
			
## -----------------------------------------------------------------------------
## 
## Función que genera un toroide (Donut), con 'nu x nv' vértices 
## (como una malla indexada con vértices replicados, es decir, no compartidos 
## entre triángulos adyacentes, lo que es equivalente a una sopa de triangulos)
## 
##
##    vertices : tabla de vértices (añade vértices)
##    indices : tabla de índices (añade índices)
##    normales  : tabla de normales (añade normales)
##    nu = numero de divisiones del 1er parámetro
##    nv = numero de divisiones del 2o parámetro
##    R  = radio mayor 
##    r  = radio menor

func generarDonutSopa( vertices: PackedVector3Array, 
							  indices: PackedInt32Array, 
							  nu: int = 32, nv: int = 12, 
							  R: float = 1.2, r: float = 0.4 ):			
	assert( nu > 2 )
	assert( nv > 2 )
	assert( vertices.size() == 0 , "GenerarDonut: los vértices no están vacios al inicio")
	assert( indices.size() == 0 , "GenerarDonut: los indices (triángulos) no están vacios al inicio")
	
	
	var c : int = 0
	
	for i in nu: 
		var u0 := float(i)/nu ; var u1 = float(i+1)/nu
		for j in nv:
			var v0 := float(j)/nv ; var v1 = float(j+1)/nv 
			var v00 := Utilidades.ParamDonut( u0,v0, r,R )
			var v01 := Utilidades.ParamDonut( u0,v1, r,R )
			var v10 := Utilidades.ParamDonut( u1,v0, r,R )
			var v11 := Utilidades.ParamDonut( u1,v1, r,R )
			
			vertices.append( v00 ); vertices.append( v11 ); vertices.append( v10 )
			indices.append( c+0 ); indices.append( c+1 ); indices.append( c+2 )
			c += 3
			
			vertices.append( v00 ); vertices.append( v01 ); vertices.append( v11 )
			indices.append( c+0 ); indices.append( c+1 ); indices.append( c+2 )
			c += 3


	
