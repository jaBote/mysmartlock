import pygame
import time
import threading
import traceback
import os

import mysql.connector
from mysql.connector import Error
from mysql.connector import errorcode

import picamera
#import RPi.GPIO as GPIO
import pigpio

import smtplib
from email.MIMEMultipart import MIMEMultipart
from email.MIMEText import MIMEText
from email.MIMEBase import MIMEBase
from email import encoders


# ¡¡¡IMPORTANTE!!!
# Configuración de la smartlock
# Propios de la smartlock
smartlock_id = 1
smartlock_code = "c81e72" # Temporal
emergency_code = "2580"
correo_smartlock = ""
pass_correo = ""
correo_admin = ""

# Credenciales DB
ipdb = ""
dbuser = ""
dbpass = ""
db = ""

# Preparación de pigpio
os.system('sudo pigpiod')
time.sleep(0.1)

# Pin servo
servo_pin = 4
open_val = 1000
closed_val = 2000

# Fin configuración, no deberías tocar abajo

pygame.init()

# Variables generales

# Configuración pigpio y servo
pi = pigpio.pi()
pi.set_servo_pulsewidth(servo_pin,closed_val) # Preparar en cerrado

# Resolución. Solo 800x480
width = 800
height = 480

# Pantalla y cursor del ratón
screen = pygame.display.set_mode((width, height), pygame.FULLSCREEN)
# screen = pygame.display.set_mode((width, height))

# pygame.mouse.set_visible(False) # Da problemas para calcular coordenadas de las entradas
pygame.mouse.set_visible(True)
pygame.mouse.set_cursor((8,8),(0,0),(0,0,0,0,0,0,0,0),(0,0,0,0,0,0,0,0)) # Oculta el cursor

# Colores. Algunos sin usar.
black = (0,0,0)
white = (255,255,255)
pink = (255,105,180)
red = (255,0,0)
blue = (0,0,255)
green = (0,255,0)
bgcolor = (114,231,255)

# Reloj de Pygame
clock = pygame.time.Clock()

# Fuentes (no textos). Fuente y tamaño. Se formatea el texto con txt_disp. Algunos sin usar
big_txt = pygame.font.SysFont("Arial",50)
mid_txt = big_txt = pygame.font.SysFont("Arial",30)
mid2_txt = pygame.font.SysFont("Arial", 25)
sml_txt = pygame.font.SysFont("Arial",15)


# Carga de imágenes del sistema visual, han de hacerse aquí para no hacerlos cada frame

# General y pantalla principal. El icono debe ser 32x32 px
#icon = pygame.image.load("res/img/icon.png")
b_noqr = pygame.image.load("res/img/noqr.png") 
b_qr = pygame.image.load("res/img/qr.png")
b_emg = pygame.image.load("res/img/emergencia.png")
b_logo = pygame.image.load("res/img/logo.png")

# Pantalla teclado emergencia
b_0 = pygame.image.load("res/img/bt0.png")
b_1 = pygame.image.load("res/img/bt1.png")
b_2 = pygame.image.load("res/img/bt2.png")
b_3 = pygame.image.load("res/img/bt3.png")
b_4 = pygame.image.load("res/img/bt4.png")
b_5 = pygame.image.load("res/img/bt5.png")
b_6 = pygame.image.load("res/img/bt6.png")
b_7 = pygame.image.load("res/img/bt7.png")
b_8 = pygame.image.load("res/img/bt8.png")
b_9 = pygame.image.load("res/img/bt9.png")
b_back = pygame.image.load("res/img/volver.png")
b_pant = pygame.image.load("res/img/pantalla.png")
b_borra = pygame.image.load("res/img/borra.png")
b_ok = pygame.image.load("res/img/ok.png")
##b_ = pygame.image.load("res/img/")

# Pantalla llave abierta
b_check = pygame.image.load("res/img/check.png")
##b_ = pygame.image.load("res/img/")


# Título del programa e icono
pygame.display.set_caption("MySmartLock")
#pygame.display.set_icon(icon)

# Variables compartidas con hilos
abrir = False
foto = False
ciclos = True

lock_online = False

abrir_local = False
foto_local = False

# Funciones de uso principal

# Crea y prepara botón
def mk_btn(img, coords, surface = screen):
    img_rect = img.get_rect()
    img_rect.center = coords
    #print (img_rect)
    surface.blit(img,img_rect)
    return img_rect

# Crea y emplaza un texto
def txt_disp(txt, coords, font, color = black, surface = screen):
    txt_surf = font.render(txt, True, color)
    txt_rect = txt_surf.get_rect()
    txt_rect.center = coords
    surface.blit(txt_surf,txt_rect)
    #print(txt_surf.get_rect())
    return


# Funciones de hilo. ¡Ojo, los sleep afectan a cada hilo de forma individualizada!
# Apertura y cierre de la cerradura
def h_open():
    global abrir
    global servo
    global open_val
    global closed_val
    
    global estado
    
    while(True):
        if abrir is True:
            estado = 2
            print "Abriendo"
            pi.set_servo_pulsewidth(servo_pin,open_val)
            time.sleep(10) # Tiempo de apertura.
            pi.set_servo_pulsewidth(servo_pin,closed_val)
            abrir = False
            print "Cerrando"
            estado = 0
        time.sleep(1)
        
# Toma de fotos y envío por web, junto a subida a la DB
# Cada foto se envía al contacto administrativo
def h_foto():
    global foto
    
    global ipdb
    global dbuser
    global dbpass
    global db
    
    global smartlock_id
    global smartlock_code
    global correo_smartlock
    global pass_correo
    global correo_admin
    
    
    archivo = "temp.jpg"
    
    while(True):
        if foto is True:
            print "Foto"
            
            try:
                # Tomar cámara y hacer foto
                with picamera.PiCamera() as picam:
                    #time.sleep(2)
                    picam.rotation = 90
                    picam.capture(archivo)
                    picam.close()
                
                # Leer en binario para codificar
                with open(archivo, 'rb') as file:
                    pic_data = file.read()
                
                # Insertar en base de datos y logs
                connection = mysql.connector.connect(host=ipdb,
                             database=db,
                             user=dbuser,
                             password=dbpass)
                cursor = connection.cursor()
                
                sql = "INSERT INTO `pics` (`pic_id`, `lock_id`, `pic_data`) VALUES (%s, %s, %s)"
                
                #Convert data into tuple format
                datos = (None,smartlock_id,pic_data)
                cursor.execute(sql, datos)
                connection.commit()
                
                id_foto = cursor.lastrowid
                
                sql = "INSERT INTO `logs` (`id`, `lockid`, `lockcode`, `origin`, `date`, `log`, `pic_id`) VALUES (NULL, '" + str(smartlock_id) + "', '" + str(smartlock_id) + "', 'l', CURRENT_TIMESTAMP, 'Foto a petición web', '" + str(id_foto) + "');"
                #print sql
                cursor.execute(sql)
                connection.commit()
            
                # Enviar correo correo_admin
                server = smtplib.SMTP('smtp.gmail.com', 587)
                server.starttls()
                server.login(correo_smartlock, pass_correo)
                
                msg = MIMEMultipart()
                msg['From'] = correo_smartlock
                msg['To'] = correo_admin
                msg['Subject'] = "Mensaje de tu Smartlock"
                
                cuerpo_mensaje = "Hemos tomado una foto"
                msg.attach(MIMEText(cuerpo_mensaje, 'plain'))

                part = MIMEBase('application', 'octet-stream')
                part.set_payload(pic_data)
                encoders.encode_base64(part)
                part.add_header('Content-Disposition', "attachment; filename= %s" % archivo)
                msg.attach(part)
                
                texto = msg.as_string()
                #print texto

                print "Enviando email"
                server.sendmail(correo_smartlock, correo_admin, texto)
                
            except mysql.connector.Error as error :
                connection.rollback()
                print("Failed inserting BLOB data into MySQL table {}".format(error))
                
            except Exception as e: # Sacar cualquier excepción
                print e
                traceback.print_exc()

            finally:
                #closing database connection.
                if(connection.is_connected()):
                    cursor.close()
                    connection.close()
                    os.remove("temp.jpg")
                    server.quit()
            
            foto = False
        time.sleep(1)
        
# Hilo de comunicación con la base de datos. No se hacen todas pero sí la mayoría de consultas aquí.
def h_base_datos():
    global ipdb
    global dbuser
    global dbpass
    global db
    
    global smartlock_id
    global smartlock_code
    
    global lock_online
    
    # Es necesario activar las variables compartidas por los otros hilos también
    global foto
    global abrir
    
    # Datos para meter en logs
    global abrir_local
    global foto_local
    
    while(True): # Consultar acciones a la base de datos
        try:
            # Conexión
            conn = mysql.connector.connect(host=ipdb, user=dbuser, passwd=dbpass, database=db)
            cursor = conn.cursor()
            
            # Sacar nuevas órdenes (solo una de cada tipo, otra cosa no tiene sentido). Hasta 3 minutos de tolerancia
            sql = "SELECT DISTINCT `action` FROM `commands` WHERE `lockid` = " + str(smartlock_id) + " AND `date` >= NOW() - INTERVAL 3 MINUTE"
            # sql = "SELECT DISTINCT `action` FROM `commands` WHERE `lockid` = " + str(smartlock_id)
            # print sql
            cursor.execute(sql)
            # n = cursor.rowcount # Ni idea de por qué falla aquí. ¿No hay commit? No necesario en select...


            n = 0
            # Procesar. OJO LAS TUPLAS SON INMUTABLES Y COMIENZAN EN 0
            resultado = cursor.fetchall()
            for x in resultado:
                n = n + 1
                #print x[0]
                if x[0] == 'o': # Abrir
                    sql = "INSERT INTO `logs` (`id`, `lockid`, `lockcode`, `origin`, `date`, `log`, `pic_id`) VALUES (NULL, " + str(smartlock_id) + ", '" + str(smartlock_code) + "', 'l', CURRENT_TIMESTAMP, 'Apertura smartlock a petición web', NULL)"
                    # print sql
                    cursor.execute(sql)
                    conn.commit()
                    abrir = True
                elif x[0] == 'p': # Foto
                    sql = "INSERT INTO `logs` (`id`, `lockid`, `lockcode`, `origin`, `date`, `log`, `pic_id`) VALUES (NULL, " + str(smartlock_id) + ", '" + str(smartlock_code) + "', 'l', CURRENT_TIMESTAMP, 'Toma de foto a petición web', NULL)"
                    # print sql
                    cursor.execute(sql)
                    conn.commit()
                    foto = True
                else:
                    print "Acción desconocida " + str(x)
            
            # Eliminar órdenes repetidas o viejas
            # Básicamente, todas menos las ejecutadas, pero las ejecutadas ya están hechas o en proceso y deben morir también
            sql = "DELETE FROM `commands` WHERE `lockid` = " + str(smartlock_id)
            # print sql
            cursor.execute(sql)
            conn.commit()
            # print "Eliminadas " + str(cursor.rowcount - n) + " entradas antiguas o repetidas"
            # Mandar al log
            if cursor.rowcount > 0:
                sql = "INSERT INTO `logs` (`id`, `lockid`, `lockcode`, `origin`, `date`, `log`, `pic_id`) VALUES (NULL, " + str(smartlock_id) + ", '" + str(smartlock_code) + "', 'l', CURRENT_TIMESTAMP, 'Se han borrado " + str(cursor.rowcount) + " peticiones antiguas o duplicadas', NULL)"
                # print sql
                cursor.execute(sql)
                conn.commit()
            
            # Peticiones locales
            if abrir_local is True:
                sql = "INSERT INTO `logs` (`id`, `lockid`, `lockcode`, `origin`, `date`, `log`, `pic_id`) VALUES (NULL, " + str(smartlock_id) + ", '" + str(smartlock_code) + "', 'l', CURRENT_TIMESTAMP, 'Apertura smartlock via local', NULL)"
                # print sql
                cursor.execute(sql)
                conn.commit()
                abrir_local = False
                
            if foto_local is True:
                sql = "INSERT INTO `logs` (`id`, `lockid`, `lockcode`, `origin`, `date`, `log`, `pic_id`) VALUES (NULL, " + str(smartlock_id) + ", '" + str(smartlock_code) + "', 'l', CURRENT_TIMESTAMP, 'Toma de foto a petición local', NULL)"
                # print sql
                cursor.execute(sql)
                conn.commit()
                foto_local = False
            
            
            # Si tenemos QR y llegamos aquí sin excepciones, buena noticia
            lock_online = True
                
            # Esperar
            time.sleep(5)
            
        except mysql.connector.Error as error :
            conn.rollback()
            print("MySQL Error {}".format(error))
            lock_online = False

        except Exception as e: # Sacar cualquier excepción
            print e
            traceback.print_exc()
            lock_online = False
        
        finally: # Cerrar MySQL. Se abrirá en el siguiente ciclo. No necesaro pero sí recomendable
            cursor.close()
            conn.close()


# Lanzamiento de hilos. Ponerlos daemonic para que cierren cuando acabe main.
h1 = threading.Thread(target=h_open)
h1.daemon = True
h1.start()

h2 = threading.Thread(target=h_foto)
h2.daemon = True
h2.start()

h3 = threading.Thread(target=h_base_datos)
h3.daemon = True
h3.start()
        
# Variables de estado de la máquina y en general. Valores iniciales.
fps = 10
ciclos = True
estado = 0

clave = ""
clave_nums = ""

try:
    while(ciclos):
        # Crear interfaz
        # Fondo de pantalla
        screen.fill(bgcolor)
        
        # Prints incondicionales: estado de conexión y fecha y hora
        txt_disp(time.strftime("%d-%m-%Y %H:%M:%S", time.gmtime()), (width-150, 25), mid_txt, black) # Fecha y hora
        
        if lock_online is True:
            txt_disp("Cerradura online", (120, 25), mid_txt, green) 
        else:
            txt_disp("Cerradura offline", (120, 25), mid_txt, red)
            
        # Imagen incondicional: logo
        b_logo_rect = mk_btn(b_logo,(100,height-80))
        
        if estado == 0: # Pant Principal
            b_emg_rect = mk_btn(b_emg, (7*width/8-30,7*height/8))
            if lock_online is True: # Cerradura, Lock ID en grande y QR
                txt_disp("Identificador: " + str(smartlock_code), (width/2,height/2-150), mid_txt, black)
                txt_disp("http://poner-aqui-direccion-a-mi-pagina-web.es/lock.php?key=" + str(smartlock_code), (width/2,height/2-120), sml_txt, black)
                b_qr_rect = mk_btn(b_qr,(width/2,height/2))
            else: # QR tachado y aviso
                txt_disp("Cerradura offline", (width/2,height/2-130), mid_txt, black)
                b_qr_rect = mk_btn(b_noqr,(width/2,height/2))
        
        elif estado == 1: # Pant Acceso emerg.
            # Teclado y pantalla numerica de clave
            cero_x = width/2 # cero_x, cero_y. Referencias para construir toda la pantalla
            cero_y = 7*height/8
            b_borra_rect = mk_btn(b_borra, (cero_x-105, cero_y))
            b_0_rect = mk_btn(b_0, (cero_x,cero_y))
            b_ok_rect = mk_btn(b_ok, (cero_x+105, cero_y))
            b_1_rect = mk_btn(b_1, (cero_x-105,cero_y-80))
            b_2_rect = mk_btn(b_2, (cero_x,cero_y-80))
            b_3_rect = mk_btn(b_3, (cero_x+105,cero_y-80))
            b_4_rect = mk_btn(b_4, (cero_x-105,cero_y-160))
            b_5_rect = mk_btn(b_5, (cero_x,cero_y-160))
            b_6_rect = mk_btn(b_6, (cero_x+105,cero_y-160))
            b_7_rect = mk_btn(b_7, (cero_x-105,cero_y-240))
            b_8_rect = mk_btn(b_8, (cero_x,cero_y-240))
            b_9_rect = mk_btn(b_9, (cero_x+105,cero_y-240))
            b_back_rect = mk_btn(b_back, (7*width/8,7*height/8))
            b_pant_rect = mk_btn(b_pant, (cero_x,cero_y-320))
            
            # Petición de clave
            txt_disp("Clave:",(cero_x-200,cero_y-320), big_txt)
            
            # Texto de la clave
            txt_disp(clave, (cero_x,cero_y-320), mid_txt)
            
        elif estado == 2: # Cerradura abierta
            b_check_rect = mk_btn(b_check, (width/2+40,height/2-60))
            txt_disp("Puerta abierta",(width/2,height/2+70), big_txt)
            txt_disp("Por favor, cierre despues de entrar",(width/2,height/2+110), big_txt)
        
        
        # Procesar eventos
        for event in pygame.event.get(): # Get clicks
            if event.type == pygame.QUIT:
                ciclos = False
                pygame.quit() # Solo en idle
                servo.stop()
                GPIO.cleanup()
            if event.type == pygame.KEYDOWN:
                if event.key == pygame.K_ESCAPE or event.key == pygame.K_q:
                    running = False
                    pygame.quit() #solo en IDLE        
            if event.type == pygame.MOUSEBUTTONDOWN: # Comprobar clicks en los elementos que interesen. 
                # Coger x e y del ratón
                x, y = event.pos
                print("mouse click:",x,y)
                if estado == 0: 
                    clave = ""
                    clave_nums = ""
                    if b_emg_rect.collidepoint(x,y):
                        estado = 1
                        
                elif estado == 1: 
                    if b_0_rect.collidepoint(x,y):
                        # Asterisco en pantalla + numero en secreto
                        if len(clave) < 10: # maximo 10 caracteres para la clave
                            clave = clave + "*"
                            clave_nums = clave_nums + "0"
                    elif b_1_rect.collidepoint(x,y):
                        if len(clave) < 10: 
                            clave = clave + "*"
                            clave_nums = clave_nums + "1"
                    elif b_2_rect.collidepoint(x,y):
                        if len(clave) < 10: 
                            clave = clave + "*"
                            clave_nums = clave_nums + "2"
                    elif b_3_rect.collidepoint(x,y):
                        if len(clave) < 10: 
                            clave = clave + "*"
                            clave_nums = clave_nums + "3"
                    elif b_4_rect.collidepoint(x,y):
                        if len(clave) < 10: 
                            clave = clave + "*"
                            clave_nums = clave_nums + "4"
                    elif b_5_rect.collidepoint(x,y):
                        if len(clave) < 10: 
                            clave = clave + "*"
                            clave_nums = clave_nums + "5"
                    elif b_6_rect.collidepoint(x,y):
                        if len(clave) < 10: 
                            clave = clave + "*"
                            clave_nums = clave_nums + "6"
                    elif b_7_rect.collidepoint(x,y):
                        if len(clave) < 10: 
                            clave = clave + "*"
                            clave_nums = clave_nums + "7"
                    elif b_8_rect.collidepoint(x,y):
                        if len(clave) < 10: 
                            clave = clave + "*"
                            clave_nums = clave_nums + "8"
                    elif b_9_rect.collidepoint(x,y):
                        if len(clave) < 10: 
                            clave = clave + "*"
                            clave_nums = clave_nums + "9"
                    
                    elif b_borra_rect.collidepoint(x,y) and len(clave)>0:
                        temp = len(clave)
                        clave = clave[:temp-1]
                    
                    elif b_ok_rect.collidepoint(x,y):
                        print "clave = " + emergency_code
                        # Comprobación acierto/fallo. Guardado en logs.
                        if clave_nums == emergency_code:
                            abrir_local = True
                            abrir = True
                        else:
                            foto_local = True
                            foto = True
                            clave = ""
                            clave_nums = ""

                        
                    elif b_back_rect.collidepoint(x,y):
                        estado = 0
                
                    # Aún en estado 1, mostrar clave como debug
                    print "Has escrito como clave: " + clave_nums
                
                elif estado == 2:
                    clave = ""
                    clave_nums = ""
        
        
        # Prints de debug en pantalla
        # txt_disp("fps:"+str(clock.get_fps()), (width/2,height/2), sml_txt)
        
        pygame.display.flip() # Pintar pantalla
        # Prints de comprobación
        # print fps
        clock.tick(fps)
    
except Exception as e: # Sacar cualquier excepción
    print e
    traceback.print_exc()

finally: 
    # Fin del bucle
    pygame.quit()
    servo.stop()
    GPIO.cleanup()

exit()    
