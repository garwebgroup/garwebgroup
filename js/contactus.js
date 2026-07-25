

  document.addEventListener("submit", async (event) => {
    event.preventDefault();
	
const form = document.getElementById("employeeForm");

    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());

    try {
      const response = await fetch("http://localhost:80/garwebgroup/backend/api/index.php?action=crud&table=contacts", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      if (!response.ok) {
          console.log ('error');
		  return;
      }

      const result = await response.json();
      console.log("Saved:", result);
      alert("Your Message has been sent");
    } catch (error) {
      console.error(error);
      alert("Message not sent");
    }
  });