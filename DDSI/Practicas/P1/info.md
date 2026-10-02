
El sistema será una aplicación de gestión para un gimnasio, destinada al uso de los administradores del centro. Permitirá gestionar la información de los usuarios, recursos, actividades y pagos. Los usuarios del gimnasio no interactuarán directamente con la aplicación.




Parte 1 - Usuario ( Hugo )
	1.1 - Crear Usuario
	1.2 - Modificar datos Usuario
	1.3 - Modificar Plan
	1.4 - Baja de Usuario
	1.5 - Consultar Información.

Parte 2 - Recursos ( Jaime )
	2.1 - Añadir Recurso (Material, instalaciónes, Suplementacion)
	2.2 - Modificar Listado de Recursos
	2.3 - Reservar recurso
	2.4 - Registrar Incidencia
	2.5 - Consultar Informacion de Recursos / Ver Disponibilidad

Parte 4 - Pagos		( Herodes )
	4.1 - Registrar Pago
	4.2 - Consultar Pagos Pendientes
	4.3 - Registrar Tarifa
	4.4 - Modificar Tarifa
	4.5 - Ver historial de pagos





	Parte 3 - Actividades 
	3.1 - Añadir Actividad ( Tipo de Actividad - Instalaciones - Plazas - Monitor)
		
		E:
			- Agente externo: 					Administrador
			- Acción: 							Solicitar la creación de una nueva actividad
			- Requisito de datos de entrada: 	Datos de la nueva actividad.
		
		BD:
			- Requisito de datos de lectura: 	Instalaciones, monitores disponibles, plazas, horario, nombre de actividad
			- Requisito de datos de escritura:  Los datos anteriores + un id propio autogenerado
		
		Salida:
			- Agente externo: 					Administrador / Sistema
			- Acción							Confirmación de que el proceso se completó correctamente.
			- Requisito de datos de salida		Sin requisito / Datos Introducidos a modo de confirmación



	3.2 - Modificar Actividad
		
		E:
			- Agente externo					Administrador
			- Acción							Solicitar la modificación de una actividad existente.
			- Requisito de datos de entrada		El id de la actividad a modificar
		
		BD:
			- Requisito de datos de lectura		Datos actuales de la actividad.
			- Requisito de datos de escritura	Datos modificados de la actividad.
			-
		
		Salida:
			- Agente externo					Administrador / Sistema	
			- Acción							Confirmación del resultado de la modificación.
			- Requisito de datos de salida		Información actualizada de la actividad.


	3.3 - Reservar Actividad
		
		E:
			- Agente externo					Administrador
			- Acción							Seleccionar actividad, reservar plazas de la actividad.
			- Requisito de datos de entrada		Usuarios apuntados y actividad seleccionada
		
		BD:
			- Requisito de datos de lectura		Id actividad y plazas restantes (o si hay plazas disponibles como booleano).
			- Requisito de datos de escritura	Id Usuario de cada reserva.
			-
		
		Salida:
			- Agente externo					Administrador / Sistema
			- Acción							Confirmar reserva.
			- Requisito de datos de salida		Datos de reserva / Datos de la actividad / Plazas restantes


	3.4 - Ver Actividades ( Activas / Proximas + Info )
		
		E:
			- Agente externo					Administrador
			- Acción							Mostrar todas las actividades en curso
			- Requisito de datos de entrada		Se podría incluso filtrar por Instalaciones/Monitor/Plazas Libres (o sin dato de entrada y mostrar todas)
		
		BD:
			- Requisito de datos de lectura		Datos de cada actividad
			- Requisito de datos de escritura	Sin dato de Escritura
			-
		
		Salida:
			- Agente externo					Administrador / Sistema
			- Acción							Crear tabla o tarjetas para cada actividad con sus datos.
			- Requisito de datos de salida		Datos de cada actividad formateados.


	3.5 - Cancelar Reserva
		
		E:
			- Agente externo					Administrador
			- Acción							Cancelar reserva de participante
			- Requisito de datos de entrada		Id/Nombre del participante y actividad
		
		BD:
			- Requisito de datos de lectura		Actividad seleccionada y participantes actuales + Participante a descartar
			- Requisito de datos de escritura	Nueva lista de participantes de la actividad
			-
		
		Salida:
			- Agente externo					Administrador / Sistema
			- Acción							Mostrar confirmación de cancelación de reserva
			- Requisito de datos de salida		Confirmación de cancelacion de reserva del usuario

