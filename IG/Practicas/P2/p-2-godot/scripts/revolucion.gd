extends Node

func generar_revolucion(
	perfil: PackedVector2Array,
	num_copias: int,
	vertices: PackedVector3Array,
	triangulos: PackedInt32Array
) -> void:

	var n_perfil := perfil.size()

	# Generar las copias rotadas del perfil
	for i in num_copias:
		var angulo := 2.0 * PI * float(i) / float(num_copias)

		var cos_a := cos(angulo)
		var sin_a := sin(angulo)

		for p in perfil:
			var x := p.x
			var y := p.y

			vertices.append(
				Vector3(
					x * cos_a,
					y,
					x * sin_a
				)
			)

	# Unir cada copia del perfil con la siguiente mediante triángulos
	for i in num_copias:
		var sig := (i + 1) % num_copias

		for j in range(n_perfil - 1):
			var a := i * n_perfil + j
			var b := sig * n_perfil + j
			var c := sig * n_perfil + j + 1
			var d := i * n_perfil + j + 1

			triangulos.append(a)
			triangulos.append(c)
			triangulos.append(b)

			triangulos.append(a)
			triangulos.append(d)
			triangulos.append(c)
