
extends Node3D

@export var velocidad: float = 60.0

func _process(delta: float) -> void:
	# W: subir
	if Input.is_key_pressed(KEY_W):
		position.y += velocidad * delta

	# S: bajar
	if Input.is_key_pressed(KEY_S):
		position.y -= velocidad * delta
