from locust import HttpUser, task, between, constant
import random

class AuthUserLoadTest(HttpUser):
    # Sin tiempo de espera entre tareas
    wait_time = constant(0)

    def on_start(self):
        """Cada usuario virtual se registra y guarda sus credenciales"""
        self.email = f"user_{random.randint(1, 1000000)}@example.com"
        self.password = "password123"
        payload = {
            "name": "Usuario Test",
            "email": self.email,
            "password": self.password
        }
        with self.client.post("/api/register", json=payload, catch_response=True) as response:
            if response.status_code == 200:
                response.success()
            else:
                response.failure(f"Error en registro: {response.status_code}")

    @task
    def login(self):
        """Simula el inicio de sesión"""
        payload = {
            "email": self.email,
            "password": self.password
        }
        with self.client.post("/api/login", json=payload, catch_response=True) as response:
            if response.status_code == 200:
                data = response.json()
                self.token = data.get("access_token")
                response.success()
            else:
                response.failure(f"Error en login: {response.status_code}")

    @task
    def logout(self):
        """Simula cierre de sesión"""
        if hasattr(self, "token") and self.token:
            headers = {"Authorization": f"Bearer {self.token}"}
            with self.client.post("/api/logout", headers=headers, catch_response=True) as response:
                if response.status_code == 200:
                    response.success()
                    self.token = None
                else:
                    response.failure(f"Error en logout: {response.status_code}")
