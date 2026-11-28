
extends Camera3D

const at := 2.5  # Ángulo con teclas (grados)
const ar := 0.5  # Ángulo con ratón (grados/píxel)

var bdrp := false

# Centro alrededor del cual orbita la cámara
@export var centro_orbita := Vector3.ZERO


# Rota la cámara alrededor del centro de la escena
func _orbitar(eje: Vector3, grados: float) -> void:
	var rotacion := Basis(eje.normalized(), deg_to_rad(grados))
	var transf := global_transform

	# Giramos la posición alrededor del centro
	transf.origin = centro_orbita + rotacion * (transf.origin - centro_orbita)

	# Giramos también la orientación de la cámara
	transf.basis = rotacion * transf.basis

	global_transform = transf


# Acerca o aleja la cámara del centro
func _zoom(factor: float) -> void:
	var direccion := global_position - centro_orbita
	var distancia := direccion.length()

	if distancia < 0.001:
		return

	var nueva_distancia: float = max(distancia * factor, 0.1)

	global_position = centro_orbita + direccion.normalized() * nueva_distancia


# Controles de cámara
func _input(event: InputEvent) -> void:

	if event is InputEventKey and event.pressed and not event.echo:
		match event.keycode:
			KEY_UP:
				_orbitar(global_transform.basis.x, at)
			KEY_DOWN:
				_orbitar(global_transform.basis.x, -at)
			KEY_RIGHT:
				_orbitar(Vector3.UP, -at)
			KEY_LEFT:
				_orbitar(Vector3.UP, at)

			KEY_MINUS, KEY_PAGEDOWN, KEY_KP_SUBTRACT:
				_zoom(1.05)

			KEY_PLUS, KEY_PAGEUP, KEY_KP_ADD:
				_zoom(1.0 / 1.05)

	elif event is InputEventMouseButton:
		match event.button_index:
			MOUSE_BUTTON_RIGHT:
				bdrp = event.pressed

			MOUSE_BUTTON_WHEEL_DOWN:
				if event.pressed:
					_zoom(1.05)

			MOUSE_BUTTON_WHEEL_UP:
				if event.pressed:
					_zoom(1.0 / 1.05)

	elif event is InputEventMouseMotion and bdrp:
		_orbitar(Vector3.UP, -event.relative.x * ar)
		_orbitar(global_transform.basis.x, -event.relative.y * ar)
