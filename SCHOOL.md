# School management inside the LMS

One system, one login page, one set of data. The library (books, loans, e-books) and the school (students, classes, marks, fees) share the same users, notifications, access rights and dashboard.

## Who logs in where
| Role | Lands on | Can do |
|---|---|---|
| `admin` / `manager` / `qa` / `user` | `/admin` | Library back-office + school modules they have been granted |
| `teacher` | `/teacher` | Own timetable, take attendance for their classes, enter marks for the subjects they teach, notices, library |
| `student` | `/student` | Timetable, attendance, published results, fees, notices, **library: request books, see loans/fines, read e-books** |
| `reader` | `/catalog` | Public e-book reading |

Teachers and students never see the library back-office. Giving a student or teacher a login automatically creates their **library membership** (students: 3 books, teachers: 10 - `config/school.php`).

## School modules (Admin → sidebar "School" / "School setup")
Students · Teachers · Classes (grade + section + class teacher + subjects/teachers) · Attendance (+ monthly report) · Timetable (clash-checked) · Exams · Marks entry · Report cards (printable) · Fees (bulk invoices, payments, balances) · Announcements · Promotion (move a class up / graduate) · Academic years · Terms · Grade levels · Subjects · Grade scale · Fee types.

Each module is an **access-rights resource**. By default only administrators see them; open **Admin → Access rights**, pick a user and tick Read / Create / Update / Delete per module (for example a "registrar" who can manage Students and Fees but nothing else).

## First-time set-up order
1. Academic years / Terms / Grade levels / Grade scale (seeded with sensible defaults).
2. Subjects → Teachers → Classes (open a class, assign subjects + teachers).
3. Students (tick "give a login" to create their account + library membership). The temporary password is shown once; they must change it at first sign-in.
4. Timetable → daily Attendance → Exams → Marks → tick **Results visible to students** on the exam to publish.
5. Fee types → Create invoices (whole class at once) → record payments.

## Student ↔ library flow
Student opens **My library** → searches → **Request** a physical book (or **Read** an e-book) → a *Draft* loan is created and library staff are notified → staff open the loan and press **Issue book** → the student is notified; overdue reminders and fines show in My library.

## Demo accounts (local / tests only, password `password`)
`student@lms.test` · `teacher@lms.test` (+ the library accounts `admin@`, `manager@`, `qa@`, `user@`, `reader@lms.test`).
Production seeding creates only the reference data and the admin from `ADMIN_EMAIL` / `ADMIN_PASSWORD`.

## Config
`config/school.php`: weekdays, period times, attendance statuses, library limits for students/teachers.
