import React from "react";
import ReactDOM from "react-dom/client";
import CampaignBooking from "@/components/CampaignBooking.jsx";
import "./index.css";

/* const root = document.getElementById("wpvite");
if (root) {
  ReactDOM.createRoot(document.getElementById("wpvite")).render(
    <React.StrictMode>
      <App />
    </React.StrictMode>
  );
} */

const root2 = document.getElementById("wpvite-frontend");

if (root2) {
  ReactDOM.createRoot(document.getElementById("wpvite-frontend")).render(
    <React.StrictMode>
      <CampaignBooking />
    </React.StrictMode>
  );
}
