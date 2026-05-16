USE primecaredental;

INSERT INTO services (name, category, description, duration, buffer, price, daily_limit, active) VALUES
('Braces', 'Preventive Care', 'Expert orthodontic care for aligned, confident smiles.', 60, 15, 4500.00, 5, 1),
('Teeth Whitening', 'Cosmetic Care', 'Advanced whitening treatment for a brighter smile.', 45, 15, 3500.00, 6, 1),
('Dental Implants', 'Restorative Care', 'Permanent, natural-looking tooth replacement with expert implant care.', 60, 15, 12000.00, 4, 1);

INSERT INTO users (full_name, username, email, password, role, contact_number) VALUES
('PrimeCare Admin', 'primeadmin', 'admin@primecare.test', '$2y$10$Mrv9aqXQCaP31xw8u56M6O9h1e3KgwxM/H2ymlk/wEYBLETymFcpu', 'admin', '09170000001'),
('Dr. Norielyn Funtanar', 'norielynf', 'norielyn@primecare.test', '$2y$10$JdjmVn1N8MPmMXxMvmFo0eWZbriPWqsJh0kGugHgdGXN53BJ38qra', 'dentist', '09170000002'),
('Dr. Nicole Marikit', 'nicolem', 'nicole@primecare.test', '$2y$10$FKrYdHfPAHDq6cHruM3a7uIe.J8V4aHcRUW2KIiMzFxLpy0X58F3e', 'dentist', '09170000003'),
('Dr. Shallom Kyle Jacinto', 'shallom', 'shallom@primecare.test', '$2y$10$FKrYdHfPAHDq6cHruM3a7uIe.J8V4aHcRUW2KIiMzFxLpy0X58F3e', 'dentist', '09170000004'),
('Front Desk Staff', 'frontdesk', 'staff@primecare.test', '$2y$10$FKrYdHfPAHDq6cHruM3a7uIe.J8V4aHcRUW2KIiMzFxLpy0X58F3e', 'secretary', '09170000005'),
('Miguel Santos', 'miguels', 'miguel@example.com', '$2y$10$5OgVRtq6GRklgU9Mv9HFbOsSJ1O2jet.h0hw6V1zTPXh41Vl6ICOO', 'patient', '09171234567');

INSERT INTO staff_details (user_id, assigned_service_id) VALUES
(2, 1),
(3, 2),
(4, 3),
(5, NULL);

INSERT INTO doctor_schedules (user_id, day_of_week, start_time, end_time) VALUES
(2, 'Monday', '09:00:00', '15:00:00'),
(2, 'Tuesday', '09:00:00', '15:00:00'),
(2, 'Wednesday', '09:00:00', '15:00:00'),
(2, 'Thursday', '09:00:00', '15:00:00'),
(3, 'Monday', '10:00:00', '16:00:00'),
(3, 'Tuesday', '10:00:00', '16:00:00'),
(3, 'Wednesday', '10:00:00', '16:00:00'),
(3, 'Thursday', '10:00:00', '16:00:00'),
(3, 'Friday', '10:00:00', '16:00:00'),
(4, 'Wednesday', '11:00:00', '16:00:00'),
(4, 'Thursday', '11:00:00', '16:00:00'),
(4, 'Friday', '11:00:00', '16:00:00'),
(5, 'Monday', '09:00:00', '17:00:00'),
(5, 'Tuesday', '09:00:00', '17:00:00'),
(5, 'Wednesday', '09:00:00', '17:00:00'),
(5, 'Thursday', '09:00:00', '17:00:00'),
(5, 'Friday', '09:00:00', '17:00:00');

INSERT INTO appointments (
    reference_code, user_id, service_id, staff_id, scheduled_date, start_time, end_time, status,
    patient_name, patient_email, patient_contact, service_name, service_fee, total_amount, notes
) VALUES
('PC-24001', 6, 1, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', '10:15:00', 'Approved',
 'Miguel Santos', 'miguel@example.com', '09171234567', 'Braces', 4500.00, 4500.00, 'Braces consultation'),
('PC-24002', 6, 2, 3, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', '11:00:00', 'Pending',
 'Miguel Santos', 'miguel@example.com', '09171234567', 'Teeth Whitening', 3500.00, 3500.00, 'First-time whitening inquiry');

INSERT INTO notifications (user_id, title, message) VALUES
(1, 'New booking request', 'Teeth whitening request is waiting for review.'),
(1, 'Reminder sent', 'Appointment reminder was sent to Miguel Santos.');
