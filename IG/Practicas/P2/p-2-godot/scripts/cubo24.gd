extends MeshInstance3D

func _ready() -> void:

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

	var normales := Utilidades.calcNormales(vertices, triangulos)

	var tablas: Array = []
	tablas.resize(Mesh.ARRAY_MAX)

	tablas[Mesh.ARRAY_VERTEX] = vertices
	tablas[Mesh.ARRAY_INDEX] = triangulos
	tablas[Mesh.ARRAY_NORMAL] = normales

	mesh = ArrayMesh.new()
	mesh.add_surface_from_arrays(Mesh.PRIMITIVE_TRIANGLES, tablas)

	var mat := StandardMaterial3D.new()
	mat.albedo_color = Color(0.5, 0.8, 0.8)
	mat.shading_mode = BaseMaterial3D.SHADING_MODE_PER_PIXEL

	material_override = mat
