#Creación de pruebas de rendimiento con Locust
from locust import HttpUser, task, between

class AuthUser(HttpUser):
    wait_time = between(1, 3)  # Tiempo aleatorio entre peticiones (simula usuarios reales)
    token = None

    @task(1)
    def register(self):
        """Prueba de registro de usuario"""
        payload = {
            "name": "Test User",
            "email": "user_test@example.com",
            "password": "password123",
        }
        self.client.post("/api/test/register", json=payload)

    @task(2)
    def login(self):
        """Prueba de login"""
        payload = {
            "email": "user_test@example.com",
            "password": "password123"
        }
        response = self.client.post("/api/test/login", json=payload)

        if response.status_code == 200 and "token" in response.json():
            self.token = response.json()["token"]

    @task(3)
    def logout(self):
        """Prueba de logout (requiere token)"""
        if self.token:
            headers = {"Authorization": f"Bearer {self.token}"}
            self.client.post("/api/logout", headers=headers)
            self.token = None
