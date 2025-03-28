<?php
session_start();
?>
<!DOCTYPE html>
<html lang='en-GB'>
<head>
<title>PHP07 A</title>
<style>
table, th, td {
  border: 1px solid black;
}
select{
    width: 15em;
    margin: 0.5em;
    padding: 0.5em;
}
input[type=text]{
    margin: 0.5em;
    padding: 0.5em;
}
input[type=submit]{
    width:5em;
    padding:0.2em;
    margin-top: 0.5em;
}
.Error {
        color: red;
        font-size: 16px; 
        font-weight: bold;
    }
</style>
</head>
<body>
<h1>Make your gym session booking</h1>
<?php
$db_hostname = "studdb.csc.liv.ac.uk";
$db_database = "sgbadede";
$db_username = "sgbadede";
$db_password = "Gbolahan4";
$db_charset = "utf8mb4";
$dsn = "mysql:host=$db_hostname;dbname=$db_database;charset=$db_charset";
$opt = array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false
);

// Check if a class was selected, store in session if true
if (isset ($_REQUEST ['select1'])){
$_SESSION ['select1'] = $_REQUEST ['select1'];
}
// Check if a date and time were selected, store in session if true
if (isset ($_REQUEST ['select2'])){
$_SESSION ['select2'] = $_REQUEST ['select2'];
}

try {
    $pdo = new PDO($dsn,$db_username,$db_password,$opt);
    // Form for selecting a class
    echo "
    <form name='form1' method='post'>
    <select name='select1' onChange='document.form1.submit()'>
    <option value=''>Select a class</option>";
    // Retrieve classes from the sessions table in the database and display them in the dropdown
    $stmt = $pdo->query("select DISTINCT Class from sessions order by Class");
    foreach($stmt as $row) {
        $selected = (isset($_REQUEST['select1']) && $_REQUEST['select1'] == $row['Class']) ? 'selected' : '';
        echo"<option value ='" , $row['Class'] , "' $selected>",$row['Class'],"</option>";
    }
    echo "
    </select>
    </form>";

     // Form for selecting date and time, and entering name and phone number
    echo "
    <form name='form2' method='post'>
    <select name='select2' required>
    <option value=''>Select a date and time</option>";
    // If a class is selected, fetch available times for that class
    if (isset($_REQUEST['select1'])&& $_REQUEST['select1'] !== ''){
        try {
            $stmt = $pdo->prepare("SELECT Times FROM sessions WHERE (Capacity > 0) AND (Class = ?)
            ORDER BY FIELD(SUBSTRING_INDEX(Times, ',', 1), 'Monday',
            'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday',
            'Sunday'),STR_TO_DATE(Times, '%W, %H:%i')");
            $stmt->execute([$_REQUEST['select1']]);
            $results = $stmt->fetchAll();
            // If all time slots are fully booked, display a message indicating the class is fully booked
            if ($results == null){
                echo "<br><span class='Error'>Sorry all ", $_REQUEST['select1'] ," sessions are fully booked.</span><br><br>";
            }else{
                // Display available times for the selected class in the dropdown
                foreach($results as $row) {
                    echo"<option value = '",$row['Times'],"'>",$row['Times'],"</option>";
                }
            }
        } catch (PDOException $e) {
            echo "$e->getMessage()";
        }
    }
    echo "
    </select><br>
    <label>Name: <br><input type='text' name='name' required></label><br>
    <label>Phone Number: <br><input type='text' name='number' required></label><br>
    <input type='submit' name='submit'><br>
    </form>";
// Validation for name and phone number input
    $nameValid = false;
    $numberValid = false;
    // Check if form has been submitted
    if (isset($_REQUEST['submit'])){
        $name = $_REQUEST['name'];
        $number = $_REQUEST['number'];
        // Validate name
        if (preg_match("/^([a-zA-Z]|')([a-zA-Z-']|\s)*\z/",$name)){
            if (preg_match("/('|-){2,}/",$name)){
                echo "<br><span class='Error'>PLEASE ENTER A NAME THAT DOES NOT INCLUDE CONSECUTIVE APOSTROPHES OR HYPHENS.<s/pan><br>";
            }
            elseif(preg_match("/-\z/",$name)){
                echo "<br><span class='Error'>PLEASE ENTER A NAME THAT DOES NOT END WITH AN HYPHEN.</span><br>";
            }else{
                $nameValid = true;
            }
        }
        else{
            echo "<br><span class='Error'>PLEASE ENTER A NAME THAT STARTS WITH A LETTER OR AN APOSTROPHE<br>-------IT MUST ALSO
            CONTAIN ONLY LETTERS, SPACES, APOSTROPHES, AND HYPHENS.</span><br>";
        }
         // Validate phone number
        if (!preg_match("/^0(\s?\d\s?){8,9}\z/",$number)){
            echo"<br><span class='Error'>PLEASE ENTER A NUMBER THAT CONSISTS OF ONLY DIGITS AND SPACES<br>---------
            IT MUST BE MADE UP OF 9 OR 10 DIGITS AND START WITH A 0.</span><br>";
        }
        else{ $numberValid = true;
        }
    }
    // If both name and phone number are valid, proceed with the booking
    if ($nameValid && $numberValid){
        try{
            // final check of availability of the selected session
            $stmt = $pdo->prepare("SELECT Capacity FROM sessions WHERE (Class=?) AND (Times =?)");
            $stmt->execute([$_SESSION['select1'], $_SESSION['select2']]);
            $result = $stmt->fetchAll();
            //if there's a slot available, book the session
            if ($result[0]['Capacity']>0){
                echo "<br><span class='Error'>BOOKING SUCCESSFUL!</span><br><br>";
                try {
                    // Decrease the capacity of the session booked by 1 in the database
                    $stmt = $pdo->prepare("UPDATE sessions SET Capacity = Capacity - 1 WHERE (Class =?) AND (Times = ?)");
                    $stmt->execute([$_SESSION['select1'],$_SESSION['select2']]);
                    // Record the booking in the database 'bookings' table
                    $now = date("y-m-d h:m:s");
                    $stmt = $pdo->prepare("INSERT INTO bookings (Name, Number, Class, Time, TimeOfBooking) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$_REQUEST['name'], $_REQUEST['number'], $_SESSION['select1'], $_SESSION['select2'],$now]);
                    // Display the user's booking history
                    $stmt = $pdo->prepare("select * from bookings where number = ? order by TimeOfBooking");
                    $stmt->execute([$_REQUEST['number']]);
                    echo "<table><thead><tr><th>Name</th><th>Number</th><th>Class</th><th>Time</th><th>Date and time booked</th>
                    </tr></thead><tbody>";
                    foreach($stmt as $row){
                        echo "<tr><td>" . $row["Name"] . "</td><td>" . $row["Number"] . "</td><td>" . $row["Class"] . "</td>
                        <td>" . $row["Time"] . "</td><td>" . $row["TimeOfBooking"] . "</td></tr>";
                    }
                    echo "</tbody></table>";
                } catch (PDOException $e) {
                }
            }else{
                echo "<br><span class='Error'>THERE ARE NO SLOTS AVAILABLE FOR THIS SESSION.</span>";
            }

            
        } catch (PDOException $e) {
            echo "$e->getMessage()";
        }
        session_unset (); session_destroy ();
    }


$pdo = NULL;
} catch (PDOException $e) {
exit("PDO Error: ".$e->getMessage()."<br>");
}

?>
</body>
</html>