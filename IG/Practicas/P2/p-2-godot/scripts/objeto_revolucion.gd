extends MeshInstance3D

func _ready() -> void:

	var perfil := PackedVector2Array([
		Vector2(0.15, -1.4),
		Vector2(0.55, -1.4),
		Vector2(0.70, -1.1),
		Vector2(0.55, -0.8),
		Vector2(0.30, -0.5),
		Vector2(0.22, -0.2),
		Vector2(0.30,  0.0),
		Vector2(0.22,  0.2),
		Vector2(0.18,  0.45),
		Vector2(0.38,  0.60),
		Vector2(0.50,  0.85),
		Vector2(0.42,  1.05),
		Vector2(0.20,  1.25),
		Vector2(0.10,  1.35)
	])
	
	##	var perfil := PackedVector2Array([
	##		Vector2(0.5, -1.0),
	##		Vector2(0.5,  1.0)
	##	])
	
	# Cono
	#var perfil := PackedVector2Array([
		#Vector2(0.7, -1.0),
		#Vector2(0.0,  1.0)
	#])
	
	# Vaso
	#var perfil := PackedVector2Array([
		#Vector2(0.4, -1.0),
		#Vector2(0.45, -0.5),
		#Vector2(0.55,  0.0),
		#Vector2(0.65,  0.7),
		#Vector2(0.7,   1.0)
	#])

	var vertices := PackedVector3Array([])
	var triangulos := PackedInt32Array([])

	Revolucion.generar_revolucion(
		perfil,
		32,
		vertices,
		triangulos
	)

	var normales := Utilidades.calcNormales(
		vertices,
		triangulos
	)

	var tablas: Array = []
	tablas.resize(Mesh.ARRAY_MAX)

	tablas[Mesh.ARRAY_VERTEX] = vertices
	tablas[Mesh.ARRAY_INDEX] = triangulos
	tablas[Mesh.ARRAY_NORMAL] = normales

	mesh = ArrayMesh.new()

	mesh.add_surface_from_arrays(
		Mesh.PRIMITIVE_TRIANGLES,
		tablas
	)

	var mat := StandardMaterial3D.new()

	mat.albedo_color = Color(0.2, 0.7, 0.9)
	mat.metallic = 0.2
	mat.roughness = 0.3
	mat.shading_mode = BaseMaterial3D.SHADING_MODE_PER_PIXEL

	material_override = mat
