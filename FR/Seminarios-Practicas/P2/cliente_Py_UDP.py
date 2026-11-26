from socket import *

#Dirección IP del Servidor
nombreServidor="127.0.0.1"

#Puerto donde esta escuchando el servidor
puertoServidor=10000
print("Envio de mensajes a traves de UDP")

#Captura desde el teclado del mensaje a enviar
mensaje=input("Escribe un mensaje en minusculas:")

#Abrimos un socket UDP
#AF_INET se refiere a ipv4
#SOCK_DGRAM hace referencia a UDP
socketCliente= socket(AF_INET,SOCK_DGRAM)

#enviamos a traves del socket el menaje (codificado en binario para poder ser enviado)
socketCliente.sendto(mensaje.encode(),(nombreServidor,puertoServidor))

#Recibimos mensaje de respuesta desde el servidor
mensaje, IPServidor = socketCliente.recvfrom(2048)
print ("Recibido: " + mensaje.decode() + " de " + IPServidor[0])

#Cerramos conexion
socketCliente.close()