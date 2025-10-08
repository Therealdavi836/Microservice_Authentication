from locust import HttpUser, task, between
import random

class AuthUserCapacityTest(HttpUser):
    wait_time = between(1, 2)
    token = None

    @task(1)
    def register(self):
        """Registro ocasional para mantener la base de datos"""
        unique_email = f"user_{random.randint(1, 1000000)}@example.com"
        payload = {
            "name": "Usuario Test",
            "email": unique_email,
            "password": "password123"
        }
        with self.client.post("/api/register", json=payload, catch_response=True) as response:
            if response.status_code == 200:
                response.success()
            else:
                response.failure(f"Error en registro: {response.status_code}")

    @task(2)
    def login(self):
        """Login constante para medir saturación del sistema"""
        payload = {
            "email": "user_test@example.com",
            "password": "password123"
        }
        with self.client.post("/api/login", json=payload, catch_response=True) as response:
            if response.status_code == 200:
                data = response.json()
                self.token = data.get("access_token")
                response.success()
            else:
                response.failure(f"Error en login: {response.status_code}")

    @task(1)
    def logout(self):
        """Logout si existe token"""
        if self.token:
            headers = {"Authorization": f"Bearer {self.token}"}
            with self.client.post("/api/logout", headers=headers, catch_response=True) as response:
                if response.status_code == 200:
                    response.success()
                    self.token = None
                else:
                    response.failure(f"Error en logout: {response.status_code}")
