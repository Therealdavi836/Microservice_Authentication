# Creación de pruebas de rendimiento con Locust
import random
from locust import HttpUser, task, between

# Clase que simula un usuario autenticado
class AuthUser(HttpUser):
    wait_time = between(1, 3)  # Tiempo aleatorio entre peticiones (simula usuarios reales)
    token = None

    @task(1)
    def register(self):
        """Prueba de registro de usuario"""
        # Genera un email único para evitar duplicados
        unique_email = f"user_{random.randint(1,100000)}@example.com"
        payload = {
            "name": "Test User",
            "email": unique_email,
            "password": "password123",
            "password_confirmation": "password123"
        }
        self.client.post("/api/register", json=payload)

    @task(2)
    def login(self):
        """Prueba de login"""
        payload = {
            "email": "user_test@example.com",  
            "password": "password123"
        }
        response = self.client.post("/api/login", json=payload)

        if response.status_code == 200:
            data = response.json()
            # Busca la clave correcta del token
            self.token = data.get("token") or data.get("access_token")

    @task(3)
    def logout(self):
        """Prueba de logout (requiere token)"""
        if self.token:
            headers = {"Authorization": f"Bearer {self.token}"}
            self.client.post("/api/logout", headers=headers)
            self.token = None
