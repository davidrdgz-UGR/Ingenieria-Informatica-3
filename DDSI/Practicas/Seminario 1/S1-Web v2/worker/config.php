<?php
// Clave compartida entre la API web y el proceso local de transacciones.
// No se envía nunca al navegador.
const PEDIDO_WORKER_SECRET = '1136a798d27f5dd0768ccd0e8da4bd8fc75d2bff934523cf9b7d2cb8acf57205';
const PEDIDO_WORKER_HOST = '127.0.0.1';
const PEDIDO_WORKER_PORT = 8765;
const PEDIDO_WORKER_IDLE_SECONDS = 1800; // 30 minutos sin actividad.
