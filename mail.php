<?php
session_start();
function ReSendOTP()
{
    $length = 6;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $_SESSION["otp"] = '';

    for ($x = 0; $x < $length; $x++) {
        $_SESSION["otp"] .=  $characters[random_int(0, strlen($characters) - 1)];
    }
}


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'C:\xampp\htdocs\PHPMailer\PHPMailer\src\Exception.php';
require 'C:\xampp\htdocs\PHPMailer\PHPMailer\src\PHPMailer.php';
require 'C:\xampp\htdocs\PHPMailer\PHPMailer\src\SMTP.php';





$Receiver = $_SESSION['receiver'];
$username =  $_SESSION['receivername'];
$pass = $_SESSION['userpass'];
$OTP = $_SESSION['otp'];


$mail = new PHPMailer(true);

try {

    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    
    $mail->Username   = 'coffeecornerofficial1@gmail.com';
    $mail->Password   = 'sfxr wvap lbwj bszs';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;


    $mail->setFrom($Receiver, 'Coffee Corner');
    $mail->addAddress($Receiver,$username );


    $mail->isHTML(true);
    $mail->Subject = "OTP";
    $mail->Body    = "
<html>
    <head>
        <style>
            .cont1{

                width: 700px;
                height: 850px;

                background-color: #285430;
                
            }
            .cont1 a {
                color: #FFF8F0; 
                text-decoration: none; 
                font-weight: bold;
            }
            .cont1 a:visited {
                color:#FFF8F0; 
            }
            body{
                    margin: 0;
                    padding: 0;
                    display: flex;
                    justify-content: center; 
                    align-items: center; 
                    color: #FFF8F0;
                    } 
            .maintxt1{
                padding: 0px;
                margin: 0px;
                font-size: 40px;
                color: #FFF8F0;
            }  
            .maintxt2{
                padding: 0px;
                margin: 0px;
                font-size: 20px;
                color: #FFF8F0;
            } 
            .maintxt3{
                color: #FFF8F0;
                font-weight: lighter;
                font-size: 15px;
                width: 480px;
            }
            .maintxt4{
                color: #FFF8F0;
            }
            .maintxt5{
                color: #FFF8F0;
                font-weight: lighter;
                font-size: 15px;
                width: 480px;
                text-align: left;
                padding-top: 20px;
            }
            .maintxt6{
                color: #FFF8F0;
                font-weight: lighter;
                font-size: 15px;
                width: 480px;
                text-align: left;
                padding-top: 15px;
            }
            





            .cont2{
                width: 500px;
                height: 150px;
                background-color: #5F8D4E;
                margin: 20px auto;
                display: flex;
                justify-content: center;
                align-items: center;
                color: #FFF8F0;
                font-size: 36px;
                font-weight: bold;
                text-align: center;
                line-height: 150px;
                padding: 70px 0; 
            }
            
            body a:visited{
                color:#FFF8F0; 
            } 
            
            body a{
                color: #FFF8F0; 
                text-decoration: none; 
                font-weight: bold;
            }
            .pos{ 
                margin: 20px auto;
                display: flex;
                justify-content: center;
                align-items: center;

                text-align: center;
            }
            .td1{
            margin-top: 0px;
            margin-bottom: 30px;
            padding-bottom: 150px;
            }
            .subtxt1{
                margin: 0;
                margin-left: 130px;
                display: inline-block; 
            }
        </style>
    </head>

    <body>
    <table width='100%' height='100%' border='0' cellspacing='0' cellpadding='0'>
        <td align='center' valign='middle'> 
        <div class='cont1'>
            <img src='https://res.cloudinary.com/dqhdp8tjm/image/upload/v1733314485/Main_Logo_hlg3gv.png' width='190px'>
            <h1 class='maintxt1'>HELLO</h1>
            <h1 class='maintxt2'>$Receiver</h1>
            <h2 class='maintxt3'>Thank you for choosing Coffee Corner. Your One-Time Password (OTP) is provided below</h2>
            <h2 class='maintxt3'>Please use this code to complete your authentication. For security purposes, do not share this code with anyone. </h2>
            <div class='cont2'>
                <h1 class='subtxt1'> $OTP</h1>
            </div>

        </div>   
    </td>
 
    </table>
    
</html>
    " ;

    
    $mail->AltBody = 'Bruhh';


    $mail->send();
} catch (Exception $e) {
    echo "Failed To Send {$mail->ErrorInfo}";
}
?>










<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <link rel="Icon" href="Main Logo.png">
    <style>
        @font-face {
            font-family: 'CustomFont';
            src: url('NeueMontreal-Medium.otf');
        }
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-image: url(Pictures/aboutpic1.jpg);
            background-repeat: no-repeat;
            background-size: contain;
        }
        .otp-card {
            background: white;
            border-radius: 10px;
            padding: 50px;
            text-align: center;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .otp-card h1 {
            color: #285430;
            margin-bottom: 10px;
        }
        .otp-card p {
            color: #666;
        }
        .otp-card-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }
        .otp-card-inputs input {
            width: 50px;
            height: 50px;
            font-size: 24px;
            text-align: center;
            border: 2px solid #ccc;
            border-radius: 5px;
        }
        .otp-card-inputs input:focus {
            border-color: #285430;
            outline: none;
        }
        .submit-btn {
            background-color: #285430;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
        }
        .submit-btn:disabled {
            background-color: #ccc;
        }




        
/* Nav */
.navContainer{
    position: absolute;
    top: -50px;
    left: -500px;
    height: 200px;
    width: 100%;

    animation-name: navAnimation;
    animation-duration: 2.5s;
    animation-timing-function: ease-in-out;

}

@keyframes navAnimation {
    0%{
        opacity: 0;
    }
    100%{
        opacity: 1;
    }   
}

@keyframes navAnimation2 {
    0%{
        opacity: 0;
    }
    100%{
       
    }   
}
nav{
    display: flex;
    position: relative;
    gap: 150px;
}
nav a{
    position: relative;
    top: 80px;
    left: 70%;
    font-family: 'CustomFont';
    font-size: 13px;
    text-transform: uppercase;
    color: #493628;
    text-decoration: none;
    font-weight: normal;
    transition: 350ms ease-in-out;
  
}

nav a:hover{
    transform: scale(1.2);
}

.nav1::after{
    content: '01';
    position: absolute;
    color: #285430;
    font-size: 11px;
    top: -5px;
    left: -15px;
    width: 10px;
    height: 10px;
}

.nav2::after{
    content: '02';
    position: absolute;
    color: #285430;
    font-size: 11px;
    top: -5px;
    left: -15px;
    width: 10px;
    height: 10px;
 
 }

 .nav3::after{
    content: '03';
    position: absolute;
    color: #285430;
    font-size: 11px;
    top: -5px;
    left: -15px;
    width: 10px;
    height: 10px;
 
 }

 .nav4::after{
    content: '04';
    position: absolute;
    color: #285430;
    font-size: 11px;
    top: -5px;
    left: -15px;
    width: 10px;
    height: 10px;
 
 }

.loginBttn{
    position: absolute;
    left: 2220px;
    top: 73px;
    color: white;
    font-family: 'CustomFont';
    width: 60px;
    font-size: 12px;
    z-index: 1;
    letter-spacing: 1px;
    transition: 350ms ease-in-out;
    
}

.loginBttn::before{
    content: "";
    position: absolute;
    top: -10px;
    left: -17px;
    width: 80px;
    height: 35px;
    background-color: #285430;
    z-index: -1;
    border-radius: 5px;
}

.loginBttn:hover{
    transform: scale(1.1);
}

.header{
    position: absolute;
    top: 0px;
    left: 0px;
    width: 100%;
    height: 70px;
    background-color: white;
    box-shadow: rgba(111, 255, 159, .1) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;
    opacity: 0.50;

    animation-name: navAnimation2;
    animation-duration: 2.5s;
    animation-timing-function: ease-in-out;

}
.mainLogoClass{
    position: absolute;
    top: 20%;
    left: 30%;
}


    </style>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const inputs = document.querySelectorAll(".otp-card-inputs input");
            const submitBtn = document.querySelector(".submit-btn");

            inputs.forEach((input, index) => {
                input.addEventListener("input", () => {
                    if (input.value.length === 1 && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                     const allFilled = Array.from(inputs).every((input) => input.value.trim().length === 1);

                    if (allFilled) {
                        form.submit(); // Automatically submit the form
                    }
                });
                   
                input.addEventListener("keydown", (e) => {
                    if (e.key === "Backspace" && input.value === "" && index > 0) {
                        inputs[index - 1].focus();
                    }
                });
            });

            document.querySelector("form").addEventListener("submit", (event) => {
                event.preventDefault();
                const enteredOTP = Array.from(inputs).map(input => input.value).join("");
                const actualOTP = "<?php echo $_SESSION['otp']; ?>";
                if (enteredOTP === actualOTP) {
                    alert("OTP Verified!");
                    alert("Account Created Successfully!");
                    window.location.href = "addAccountDatabase.php";

                } else {
                    alert("Incorrect OTP. Please try again.");
                    inputs.forEach(input => input.value = ""); // Clear all input fields
                    inputs[0].focus(); // Set focus back to the first input
                }
            });
        });
    </script>
</head>
<body>
           
                        <div class="header">
                            
                        </div>
            <div class="navContainer">
                                
                                <nav>
                                    <a href="./Main.php" class="nav1">Home</a>
                                    <a href="./About.html" class="nav2">About Us</a>
                                    <a href="./Contact.html" class="nav3">Contact</a>
                                    <a href="./Menu(Guest).html" class="nav4">Menu</a>   
                                </nav>
                                <a href="./Main.html"><img src="Pictures/MainLogoColoredInverted.png" width="100px" class="mainLogoClass"></a>
                                <a href="#" onclick="labas()"><h1 class="loginBttn">LOG IN</h1></a>
                        </div>
    <div class="otp-card">
        <h1>Enter OTP</h1>
        <p>Your OTP has been sent to your email.</p>
        <form>
            <div class="otp-card-inputs">
                <input type="text" maxlength="1" required autofocus>
                <input type="text" maxlength="1" required>
                <input type="text" maxlength="1" required>
                <input type="text" maxlength="1" required>
                <input type="text" maxlength="1" required>
                <input type="text" maxlength="1" required>
            </div>
            <button type="submit" class="submit-btn">Submit</button>
        </form>
    </div>
</body>
</html>