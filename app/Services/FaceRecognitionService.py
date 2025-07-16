import cv2
import numpy as np
import face_recognition
import os
import json
import sys
from datetime import datetime
import tensorflow as tf
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import Dense, Conv2D, MaxPooling2D, Flatten, Dropout
from tensorflow.keras.preprocessing.image import ImageDataGenerator

class FaceRecognitionService:
    def __init__(self):
        # Pastikan path absolut
        self.base_path = os.path.dirname(os.path.abspath(__file__))
        self.dataset_path = os.path.join(self.base_path, '..', '..', 'storage', 'app', 'public', 'face_dataset')
        self.model_path = os.path.join(self.base_path, '..', '..', 'storage', 'app', 'public', 'face_models')

        # Buat direktori jika belum ada
        os.makedirs(self.dataset_path, exist_ok=True)
        os.makedirs(self.model_path, exist_ok=True)

        print(f"Dataset path: {self.dataset_path}")
        print(f"Model path: {self.model_path}")

        # Inisialisasi model GAN
        self.gan_model = self._build_gan_model()

    def _build_gan_model(self):
        model = Sequential([
            Conv2D(32, (3, 3), activation='relu', input_shape=(64, 64, 3)),
            MaxPooling2D((2, 2)),
            Conv2D(64, (3, 3), activation='relu'),
            MaxPooling2D((2, 2)),
            Conv2D(64, (3, 3), activation='relu'),
            Flatten(),
            Dense(64, activation='relu'),
            Dropout(0.5),
            Dense(1, activation='sigmoid')
        ])
        model.compile(optimizer='adam', loss='binary_crossentropy', metrics=['accuracy'])
        return model

    def _calculate_histogram(self, image):
        # Konversi ke HSV
        hsv = cv2.cvtColor(image, cv2.COLOR_BGR2HSV)

        # Hitung histogram
        hist = cv2.calcHist([hsv], [0, 1], None, [180, 256], [0, 180, 0, 256])

        # Normalisasi histogram
        cv2.normalize(hist, hist, alpha=0, beta=1, norm_type=cv2.NORM_MINMAX)

        return hist

    def _compare_histograms(self, hist1, hist2):
        return cv2.compareHist(hist1, hist2, cv2.HISTCMP_CORREL)

    def capture_dataset(self, user_id, image_path):
        print(f"Memulai pengambilan dataset untuk user {user_id}...")
        print(f"Image path: {image_path}")

        try:
            # Baca gambar
            frame = cv2.imread(image_path)
            if frame is None:
                print(f"Error: Tidak dapat membaca gambar dari {image_path}")
                return False

            # Konversi ke RGB untuk face_recognition
            rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)

            # Deteksi wajah
            face_locations = face_recognition.face_locations(rgb_frame)
            if not face_locations:
                print("Error: Tidak ada wajah terdeteksi dalam gambar")
                return False

            # Ambil wajah pertama yang terdeteksi
            top, right, bottom, left = face_locations[0]

            # Tambahkan margin
            margin = 20
            top = max(0, top - margin)
            left = max(0, left - margin)
            bottom = min(frame.shape[0], bottom + margin)
            right = min(frame.shape[1], right + margin)

            face_image = frame[top:bottom, left:right]

            # Resize ke ukuran standar
            face_image = cv2.resize(face_image, (200, 200))

            # Simpan gambar
            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            filename = f"user_{user_id}_{timestamp}.jpg"
            filepath = os.path.join(self.dataset_path, filename)
            cv2.imwrite(filepath, face_image)

            print(f"Gambar berhasil disimpan di: {filepath}")
            return True

        except Exception as e:
            print(f"Error dalam capture_dataset: {str(e)}")
            return False

    def save_model(self, user_id, face_encodings):
        model_file = os.path.join(self.model_path, f"user_{user_id}_model.json")
        with open(model_file, 'w') as f:
            json.dump({
                'user_id': user_id,
                'face_encodings': [enc.tolist() for enc in face_encodings]
            }, f)

    def load_model(self, user_id):
        model_file = os.path.join(self.model_path, f"user_{user_id}_model.json")
        if os.path.exists(model_file):
            with open(model_file, 'r') as f:
                data = json.load(f)
                return [np.array(enc) for enc in data['face_encodings']]
        return None

    def verify_face(self, image_path, user_id):
        try:
            # Baca gambar
            image = cv2.imread(image_path)
            if image is None:
                print("Error: Tidak dapat membaca gambar")
                return False

            # Deteksi wajah
            face_locations = face_recognition.face_locations(image)
            if not face_locations:
                print("Tidak ada wajah terdeteksi")
                return False

            # Ambil wajah pertama
            top, right, bottom, left = face_locations[0]
            face_image = image[top:bottom, left:right]

            # Hitung histogram wajah
            face_hist = self._calculate_histogram(face_image)

            # Load model
            known_encodings = self.load_model(user_id)
            if not known_encodings:
                print("Model tidak ditemukan")
                return False

            # Bandingkan dengan dataset
            max_similarity = 0
            for known_hist in known_encodings:
                similarity = self._compare_histograms(face_hist, known_hist)
                max_similarity = max(max_similarity, similarity)

            # Gunakan GAN untuk verifikasi tambahan
            face_resized = cv2.resize(face_image, (64, 64))
            face_array = np.expand_dims(face_resized, axis=0)
            gan_prediction = self.gan_model.predict(face_array)[0][0]

            # Kombinasikan hasil histogram dan GAN
            is_match = max_similarity > 0.7 and gan_prediction > 0.5

            print(json.dumps({
                'is_match': bool(is_match),
                'histogram_similarity': float(max_similarity),
                'gan_confidence': float(gan_prediction)
            }))

            return is_match

        except Exception as e:
            print(f"Error dalam verifikasi wajah: {str(e)}")
            return False

if __name__ == "__main__":
    service = FaceRecognitionService()

    if len(sys.argv) < 4:
        print("Usage: python FaceRecognitionService.py capture_dataset [user_id] [image_path]")
        sys.exit(1)

    command = sys.argv[1]
    user_id = sys.argv[2]
    image_path = sys.argv[3]

    if command == "capture_dataset":
        success = service.capture_dataset(user_id, image_path)
        sys.exit(0 if success else 1)
    elif command == "verify_face" and len(sys.argv) > 3:
        image_path = sys.argv[3]
        service.verify_face(image_path, user_id)
    else:
        print("Invalid command")
        sys.exit(1)
