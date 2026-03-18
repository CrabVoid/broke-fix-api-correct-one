-- Stored procedure to create an application with all validations
-- Mirrors the Application::createForUser() logic
--
-- Usage: CALL create_application(user_id, internship_id, motivation_letter, @success, @message, @app_id);
-- Example:
--   CALL create_application(1, 1, 'I am motivated...', @success, @message, @app_id);
--   SELECT @success, @message, @app_id;

DROP PROCEDURE IF EXISTS create_application;

CREATE PROCEDURE create_application(
    IN p_internship_id INT,
    IN p_motivation_letter TEXT,
    OUT p_message VARCHAR(255),
    OUT p_application_id INT,
    OUT p_success BOOLEAN
)
proc_body: BEGIN
    -- Declare variables for validation
    DECLARE v_group_id INT DEFAULT NULL;
    DECLARE v_group_internship_id INT DEFAULT NULL;
    DECLARE v_existing_application INT DEFAULT NULL;

    -- Start transaction

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Database error occurred';
        SET p_application_id = NULL;
    END;


    START TRANSACTION;

    -- Validation 1: Check if user exists and get their group
    SELECT groups_id INTO v_group_id
    FROM users
    WHERE id = p_user_id;

    IF v_group_id IS NULL THEN
        SET p_success = FALSE;
        SET p_message = 'User does not exist or does not belong to any group';
        SET p_application_id = NULL;
        ROLLBACK;
        LEAVE proc_body;
    END IF;

    -- Validation 2: Check if user's group is associated with this internship
    SELECT id INTO v_group_internship_id
    FROM group_internships
    WHERE group_id = v_group_id
      AND internship_id = p_internship_id
    LIMIT 1;

    IF v_group_internship_id IS NULL THEN
        SET p_success = FALSE;
        SET p_message = 'User''s group is not associated with this internship';
        SET p_application_id = NULL;
        ROLLBACK;
        LEAVE proc_body;
    END IF;

    -- Validation 3: Check for duplicate application
    SELECT id INTO v_existing_application
    FROM applications
    WHERE users_id = p_user_id
      AND internships_id = p_internship_id
    LIMIT 1;

    IF v_existing_application IS NOT NULL THEN
        SET p_success = FALSE;
        SET p_message = 'User has already applied to this internship';
        SET p_application_id = NULL;
        ROLLBACK;
        LEAVE proc_body;
    END IF;

    -- All validations passed - create the application
    INSERT INTO applications (users_id, internships_id, motivation_letter, created_at, updated_at)
    VALUES (p_user_id, p_internship_id, p_motivation_letter, NOW(), NOW());

    SET p_application_id = LAST_INSERT_ID();
    SET p_success = TRUE;
    SET p_message = 'Application created successfully';

    COMMIT;
END;
