
extends Node3D

@export var velocidad_giro: float = 30.0

func _process(delta: float) -> void:
	if Input.is_key_pressed(KEY_A):
		rotation.y += deg_to_rad(velocidad_giro * delta)

	if Input.is_key_pressed(KEY_D):
		rotation.y -= deg_to_rad(velocidad_giro * delta)
