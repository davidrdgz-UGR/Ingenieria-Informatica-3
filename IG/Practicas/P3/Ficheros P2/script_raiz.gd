extends Node3D

## Ver: 
## https://docs.godotengine.org/en/4.4/classes/class_viewport.html#enum-viewport-debugdraw

var dibujar_aristas : bool = false 


func _init():
	RenderingServer.set_debug_generate_wireframes(true)

func _unhandled_key_input( key_event ):
	
	if key_event.keycode == KEY_W and not key_event.pressed :	#  Actua al soltarla 
		
		dibujar_aristas = not dibujar_aristas					# Invierte la variable 'dibujar_aristas'
		
		var viewport = get_viewport()							# Obtiene el objeto viewport para usarlo despues (Es donde se renderiza la escena)
		
		if dibujar_aristas:										# Si esta activo dibuja las aristas
			viewport.debug_draw = Viewport.DEBUG_DRAW_WIREFRAME	# ESTO DIBUJA EL WIREFRAME / ARISTAS -> DEBUG_DRAW_WIREFRAME
			print("Dibujar en modo aristas: activado")
		else:
			viewport.debug_draw = Viewport.DEBUG_DRAW_DISABLED	# Desactiva las aristas
			print("Dibujar en modo aristas: desactivado")
