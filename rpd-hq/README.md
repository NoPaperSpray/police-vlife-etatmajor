# RPD HQ Management System

## Setup and Access Instructions

This is a web application designed to help manage the Rockford Police Department Headquarters (RPD HQ) for your GMod role-playing server.

### Prerequisites:
- **Laragon (or any WAMP/LAMP stack):** Ensure your Laragon environment (Apache, MySQL, PHP) is running. If not, open the Laragon control panel and click "Start All".

### How to Access the Application:

1.  **Place the Project:** Ensure the `rpd-hq` folder is located in your Laragon's `www` directory (e.g., `C:\laragon\www\rpd-hq`).
2.  **Database Setup (Already done by the AI, but for reference):**
    *   The AI has already attempted to create a MySQL database named `rpd_hq` and the necessary tables (`agents`, `evaluations`, `sanctions`). If you encounter any database-related issues, ensure MySQL is running and check `includes/db.php` for connection details.
3.  **Open in Browser:** Open your web browser and navigate to `http://localhost/rpd-hq/`.

You should now see the RPD HQ Dashboard. You can navigate through the different sections using the top menu.

## Next Steps:
The basic structure is in place. We can now proceed to implement the functionalities for:
*   Adding/Viewing/Editing Agents
*   Adding/Viewing Évaluations (Comments) for Agents
*   Adding/Viewing Sanctions for Agents
*   Developing the "Promotions" logic based on evaluations and sanctions.
