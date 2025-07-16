import cv2
import numpy as np
import face_recognition
from PIL import Image
import io
import base64

class FaceRecognitionService:
    def __init__(self):
        self.known_face_encodings = []
        self.known_face_names = []

    def encode_face(self, image_data):
        # Convert base64 image to numpy array
        image_bytes = base64.b64decode(image_data)
        image = Image.open(io.BytesIO(image_bytes))
        image_np = np.array(image)

        # Convert to RGB (face_recognition uses RGB)
        if len(image_np.shape) == 3 and image_np.shape[2] == 3:
            if image_np.dtype != np.uint8:
                image_np = image_np.astype(np.uint8)
            rgb_image = cv2.cvtColor(image_np, cv2.COLOR_BGR2RGB)
        else:
            raise ValueError("Invalid image format")

        # Find face locations and encodings
        face_locations = face_recognition.face_locations(rgb_image)
        if not face_locations:
            raise ValueError("No face detected in the image")

        face_encodings = face_recognition.face_encodings(rgb_image, face_locations)
        if not face_encodings:
            raise ValueError("Could not encode face")

        return face_encodings[0].tolist()

    def verify_face(self, known_face_encoding, unknown_image_data):
        try:
            # Convert base64 image to numpy array
            image_bytes = base64.b64decode(unknown_image_data)
            image = Image.open(io.BytesIO(image_bytes))
            image_np = np.array(image)

            # Convert to RGB
            if len(image_np.shape) == 3 and image_np.shape[2] == 3:
                if image_np.dtype != np.uint8:
                    image_np = image_np.astype(np.uint8)
                rgb_image = cv2.cvtColor(image_np, cv2.COLOR_BGR2RGB)
            else:
                raise ValueError("Invalid image format")

            # Find face locations and encodings
            face_locations = face_recognition.face_locations(rgb_image)
            if not face_locations:
                return False

            face_encodings = face_recognition.face_encodings(rgb_image, face_locations)
            if not face_encodings:
                return False

            # Compare faces
            known_encoding = np.array(known_face_encoding)
            matches = face_recognition.compare_faces([known_encoding], face_encodings[0], tolerance=0.6)

            return matches[0]

        except Exception as e:
            print(f"Error in face verification: {str(e)}")
            return False
