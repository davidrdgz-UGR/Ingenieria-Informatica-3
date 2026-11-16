
PROBLEMA 2 / 4 -> Caen examen


PROBLEMA 1: Indique el resultado que produce la ejecución de las siguientes órdenes sobre un sistema Linux con el monitor sar instalado:

1. sar
2. sar -A
3. sar -u 1 30
4. sar -uB -f /var/log/sa/08
5. sar -d -s 12:30:00 -e 18:15:00 -f /var/log/sa/08
6. sadc
7. sadc 2 4
8. sadc 2 4 fichero



PROBLEMA 2: Después de instrumentar un programa con la herramienta gprof el re- sultado
obtenido ha sido el siguiente:

Flat profile:
Each sample counts as 0.01 seconds


El grafo de dependencias muestra que invierte() es llamado desde el procedimiento
reduce().
1. ¿Cuánto tarda en ejecutarse el código propio del procedimiento reduce()?
2. ¿Cuál es el procedimiento más lento del programa? ¿Y el más rápido?
3. Si el procedimiento más lento de todos se sustituye por otro tres veces más rápido,
¿cuánto tiempo tardará en ejecutarse el programa?
4. Si el procedimiento invierte() se sustituye por una nueva versión cuatro veces más
rápida, ¿qué mejora se obtendrá en el tiempo de ejecución?
5. Calcule cuál es la aceleración máxima que se podría conseguir en el tiempo de
ejecución mediante la optimización del código del procedimiento invierte().