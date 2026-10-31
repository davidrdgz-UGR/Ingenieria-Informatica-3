extends MeshInstance3D

var ver_normales : bool = false ## se cambia en run-time con la  tecla 'N' (ver _undhandled_key_input)
var mesh_normales : MeshInstance3D = null 

func _ready() -> void:
	
	## crear las tablas de vértices y triángulos de un Donut 
	var vertices   := PackedVector3Array([])
	var triangulos := PackedInt32Array([])
	Utilidades.generarDonut( vertices, triangulos )
		
	var normales := Utilidades.calcNormales( vertices, triangulos )
	assert( vertices.size() == normales.size() )
			
	## inicializar el array con las tablas
	var tablas : Array = []   ## tabla vacía incialmente
	tablas.resize( Mesh.ARRAY_MAX ) ## redimensionar al tamaño adecuado
	tablas[ Mesh.ARRAY_VERTEX ] = vertices
	tablas[ Mesh.ARRAY_INDEX  ] = triangulos
	tablas[ Mesh.ARRAY_NORMAL ] = normales
	
	## crear e inicialzar el objeto 'mesh' de este nodo 
	mesh = ArrayMesh.new() ## crea malla en modo diferido, vacía
	mesh.add_surface_from_arrays( Mesh.PRIMITIVE_TRIANGLES, tablas )
	
	## crear un material
	var mat := StandardMaterial3D.new()
	mat.albedo_color = Color( 1.0, 0.5, 0.2 )
	mat.metallic = 0.3
	mat.roughness = 0.2
	
	## mat.shading_mode = BaseMaterial3D.SHADING_MODE_PER_PIXEL
	mat.shading_mode = BaseMaterial3D.SHADING_MODE_PER_VERTEX
	
	material_override = mat
	
	## añadir normales para verlas
	## mesh_normales = Algoritmos.generarSegmentosNormales( vertices, normales, 0.15, Color(1.0,1.0,1.0) )
	## mesh_normales.visible = ver_normales
	## add_child( mesh_normales )
	
	
	
func _unhandled_key_input( key_event: InputEvent ):

	if mesh_normales == null:
		return 
			
	if key_event.keycode == KEY_N and not key_event.pressed :
		ver_normales = not ver_normales
		if ver_normales:
			print("Normales del Donut: activado")
		else:
			print("Normales del Donut: desactivado")
		mesh_normales.visible = ver_normales
		
	
