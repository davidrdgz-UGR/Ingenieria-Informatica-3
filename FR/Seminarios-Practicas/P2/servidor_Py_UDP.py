from socket import *

#Puerto donde esta escuchando el servidor
puertoServidor=10000

#Abrimos un socket UDP
#AF_INET se refiere a ipv4
#SOCK_DGRAM hace referencia a UDP
socketServidor = socket(AF_INET,SOCK_DGRAM)

#Mediante bind especificamos que el socket es modo servidor
socketServidor.bind(("",puertoServidor))
print ("Servidor listo para recibir mensajes")

while True:
	#Queda a la espera de recibir mensaje desde un cliente
	mensaje, IPCliente = socketServidor.recvfrom(2048)
	print ("Recibido: " + mensaje.decode() + " de " + IPCliente[0])

	#La respuesta es el mismo texto en mayuscula
	respuesta= "OK: " + mensaje.decode().upper()
	socketServidor.sendto(respuesta.encode(),IPCliente)