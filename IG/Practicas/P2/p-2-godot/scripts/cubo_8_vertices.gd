extends MeshInstance3D

func _ready() -> void:

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
