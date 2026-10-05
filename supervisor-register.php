<?php
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>AI-ITMS — Supervisor Registration</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f7fb;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
            color: #14213d;
            padding: 30px 15px;
        }

        .container {
            width: 100%;
            max-width: 620px;
        }

        .brand {
            text-align: center;
            margin-bottom: 24px;
        }

        .brand-logo {
            width: 50px;
            height: 50px;
            margin: 0 auto 12px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #14213d;
            color: white;
            font-size: 22px;
            font-weight: 700;
        }

        .brand h1 {
            margin: 0;
            font-size: 26px;
        }

        .brand p {
            margin: 6px 0 0;
            color: #68758a;
            font-size: 14px;
        }

        .card {
            background: #fff;
            border-radius: 18px;
            padding: 30px;
            box-shadow:
                0 10px 35px rgba(20, 33, 61, .08);
        }

        .card h2 {
            margin: 0 0 6px;
            font-size: 21px;
        }

        .subtitle {
            margin: 0 0 24px;
            color: #68758a;
            font-size: 13px;
            line-height: 1.5;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .field {
            margin-bottom: 17px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 600;
        }

        input,
        select {
            width: 100%;
            border: 1px solid #dbe2ec;
            border-radius: 10px;
            padding: 12px 13px;
            font-size: 14px;
            outline: none;
            background: white;
        }

        input:focus,
        select:focus {
            border-color: #315a8a;
        }

        .btn {
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 13px;
            background: #14213d;
            color: white;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn:disabled {
            opacity: .65;
            cursor: not-allowed;
        }

        .message {
            display: none;
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.5;
        }

        .message.success {
            display: block;
            background: #eaf8ef;
            color: #176b3a;
        }

        .message.error {
            display: block;
            background: #fff0f0;
            color: #a52828;
        }

        .footer {
            margin-top: 18px;
            text-align: center;
            font-size: 13px;
            color: #68758a;
        }

        .footer a {
            color: #315a8a;
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 600px) {

            .grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .card {
                padding: 22px;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <div class="brand">

        <div class="brand-logo">
            T
        </div>

        <h1>
            AI-ITMS
        </h1>

        <p>
            Supervisor Account Setup
        </p>

    </div>


    <div class="card">

        <h2>
            Create Supervisor Account
        </h2>

        <p class="subtitle">
            Create a supervisor account that can sign in through
            the main AI-ITMS login page.
        </p>


        <div
            id="message"
            class="message"
        ></div>


        <form
            id="supervisorForm"
        >

            <div class="grid">

                <div class="field">

                    <label>
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="fullName"
                        placeholder="e.g. Engr. John Ade"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Staff ID
                    </label>

                    <input
                        type="text"
                        id="staffId"
                        placeholder="e.g. SUP002"
                        required
                    >

                </div>

            </div>


            <div class="field">

                <label>
                    Department
                </label>

                <input
                    type="text"
                    id="department"
                    placeholder="e.g. Software Development"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    placeholder="supervisor@example.com"
                    required
                >

            </div>


            <div class="grid">

                <div class="field">

                    <label>
                        Phone
                    </label>

                    <input
                        type="text"
                        id="phone"
                        placeholder="08012345678"
                    >

                </div>
                <div class="field">

                    <label>
                        Organization
                    </label>

                    <select
                        id="organizationId"
                        required
                    >

                        <option value="">
                            Select organization
                        </option>

                        <option value="1">
                            TechNova Solutions Ltd.
                        </option>

                        <option value="2">
                            CodeCraft Africa
                        </option>

                        <option value="3">
                            ByteWave Systems
                        </option>

                        <option value="4">
                            DataPrime Analytics
                        </option>

                        <option value="5">
                            GreenGrid Power Systems
                        </option>

                        <option value="6">
                            Zenith Capital Advisory
                        </option>

                        <option value="7">
                            PrimeWave Media Group
                        </option>

                    </select>

                </div>


                <div class="field">

                    <label>
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        placeholder="Minimum 6 characters"
                        minlength="6"
                        required
                    >

                </div>

            </div>


            <button
                type="submit"
                class="btn"
                id="submitBtn"
            >
                Create Supervisor Account
            </button>

        </form>


        <div class="footer">

            Already have an account?

            <a href="/aioims/">
                Go to AI-ITMS Login
            </a>

        </div>

    </div>

</div>


<script>

const form =
    document.getElementById(
        "supervisorForm"
    );

const message =
    document.getElementById(
        "message"
    );

const submitBtn =
    document.getElementById(
        "submitBtn"
    );


form.addEventListener(
    "submit",
    async function (event) {

        event.preventDefault();


        message.className =
            "message";

        message.textContent =
            "";


        const payload = {

        full_name:
            document.getElementById("fullName").value.trim(),

        staff_id:
            document.getElementById("staffId").value.trim(),

        organization_id:
            Number(
            document
                .getElementById("organizationId")
                .value
            ),

        department:
            document.getElementById("department").value.trim(),

        email:
            document.getElementById("email").value.trim(),

        phone:
            document.getElementById("phone").value.trim(),

        password:
            document.getElementById("password").value

        };


        try {

            submitBtn.disabled = true;

            submitBtn.textContent =
                "Creating account...";


            const response =
                await fetch(
                    "/aioims/api/auth/register-supervisor.php",
                    {
                        method: "POST",

                        headers: {
                            "Accept":
                                "application/json",

                            "Content-Type":
                                "application/json"
                        },

                        credentials:
                            "include",

                        body:
                            JSON.stringify(
                                payload
                            )
                    }
                );


            const result =
                await response.json();


            console.log(
                "SUPERVISOR REGISTRATION:",
                result
            );


            if (!result.success) {

                throw new Error(
                    result.message ||
                    "Unable to create supervisor account."
                );
            }


            message.className =
                "message success";

            message.textContent =
                "Supervisor account created successfully. You can now log in through the main AI-ITMS login page.";


            form.reset();


        } catch (error) {

            console.error(
                "Supervisor registration error:",
                error
            );

            message.className =
                "message error";

            message.textContent =
                error.message;


        } finally {

            submitBtn.disabled =
                false;

            submitBtn.textContent =
                "Create Supervisor Account";
        }

    }
);

</script>

</body>

</html>