@tool
extends Node3D
# Ejes 3D con flechas sólidas (X=rojo, Y=verde, Z=azul)
# Compatible con Godot 4.4 (usa CylinderMesh con top_radius/bottom_radius)

@export var length: float = 1.5 : set = _set_length
@export var shaft_radius: float = 0.05 : set = _set_shaft_radius
@export var cone_size: float = 0.15 : set = _set_cone_size
@export var visible_in_game: bool = true : set = _set_visible

const AXES_NODE_NAME := "Ejes"

var axes_node: Node3D

func _ready() -> void:
	_rebuild_axes()

func _set_length(v: float) -> void:
	length = max(v, 0.01)
	_rebuild_axes()

func _set_shaft_radius(v: float) -> void:
	shaft_radius = max(v, 0.001)
	_rebuild_axes()

func _set_cone_size(v: float) -> void:
	cone_size = clampf(v, 0.01, length * 0.9)
	_rebuild_axes()

func _set_visible(v: bool) -> void:
	visible_in_game = v
	if axes_node:
		axes_node.visible = v

func _rebuild_axes() -> void:
	if axes_node:
		axes_node.queue_free()

	axes_node = Node3D.new()
	axes_node.name = AXES_NODE_NAME
	if Engine.is_editor_hint():
		# Para que se guarde en la escena si lo creas en el editor
		axes_node.owner = get_tree().edited_scene_root
	add_child(axes_node)

	# Eje X (rojo), Y (verde), Z (azul)
	_make_axis_x()
	_make_axis_y()
	_make_axis_z()

	axes_node.visible = visible_in_game

func _make_axis_x() -> void:
	var axis := _build_axis(Color(1, 0, 0))
	# Orienta el eje local +Y hacia +X: rotación -90º alrededor de Z
	axis.rotation_degrees = Vector3(0, 0, -90)
	axes_node.add_child(axis)

func _make_axis_y() -> void:
	var axis := _build_axis(Color(0, 1, 0))
	# Ya apunta a +Y por defecto
	axes_node.add_child(axis)

func _make_axis_z() -> void:
	var axis := _build_axis(Color(0, 0.6, 1))
	# Orienta el eje local +Y hacia +Z: rotación +90º alrededor de X
	axis.rotation_degrees = Vector3(90, 0, 0)
	axes_node.add_child(axis)

func _build_axis(color: Color) -> Node3D:
	var axis := Node3D.new()

	# Material
	var mat := StandardMaterial3D.new()
	mat.albedo_color = color

	# ---- Cuerpo (cilindro de radio uniforme) ----
	var shaft := MeshInstance3D.new()
	var shaft_mesh := CylinderMesh.new()
	shaft_mesh.top_radius = shaft_radius
	shaft_mesh.bottom_radius = shaft_radius
	shaft_mesh.height = max(length - cone_size, 0.01)
	shaft_mesh.radial_segments = 24
	shaft.mesh = shaft_mesh
	shaft.material_override = mat
	# Colocado a lo largo del +Y local
	shaft.position = Vector3(0, shaft_mesh.height * 0.5, 0)
	axis.add_child(shaft)

	# ---- Punta (cono simulado con CylinderMesh: top_radius = 0) ----
	var cone := MeshInstance3D.new()
	var cone_mesh := CylinderMesh.new()
	cone_mesh.top_radius = 0.0
	cone_mesh.bottom_radius = shaft_radius * 2.0
	cone_mesh.height = cone_size
	cone_mesh.radial_segments = 24
	cone.mesh = cone_mesh
	cone.material_override = mat
	cone.position = Vector3(0, shaft_mesh.height + cone_mesh.height * 0.5, 0)
	axis.add_child(cone)

	return axis
