
extends Node3D

@export var radio: float = 5.0
@export var velocidad: float = 1.0
@export var altura: float = 0.0
@export var orientacion_extra: float = 0.0

var angulo: float = 0.0

func _process(delta: float) -> void:
	# Aumentamos el ángulo con el tiempo
	angulo += velocidad * delta

	# Calculamos la posición circular
	position.x = radio * cos(angulo)
	position.z = radio * sin(angulo)
	position.y = altura

	# Orientamos al lobo en la dirección del movimiento
	rotation.y = PI - angulo + deg_to_rad(orientacion_extra)
