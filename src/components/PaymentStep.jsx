import { useState, useEffect } from "react";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Loader2 } from "lucide-react";
import axios from "axios";
import StepHeader from "./StepHeader";
import useCampaignStore from "@/stores/useCampaignStore";

const PaymentStep = () => {
    const [paymentMethods, setPaymentMethods] = useState([]);
    const [selectedMethod, setSelectedMethod] = useState("");
    const [loading, setLoading] = useState(true);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState(null);
    const [customer, setCustomer] = useState({
        first_name: "",
        last_name: "",
        email: "",
        phone: ""
    });

    const {
        currentStep,
        campaignType,
        billboard,
        radio,
        totalOrderCost,
        setCurrentStep,
    } = useCampaignStore();

    useEffect(() => {
        fetchPaymentMethods();
        fetchUserDetails();
    }, []);

    const fetchPaymentMethods = async () => {
        try {
            const response = await axios.get("http://localhost/wordpress/wp-json/adrentals/v1/available-gateways");
            setPaymentMethods(response.data);
            if (response.data.length > 0) {
                setSelectedMethod(response.data[0].id);
            }
        } catch (error) {
            setError("Failed to load payment methods.");
        } finally {
            setLoading(false);
        }
    };

    const fetchUserDetails = async () => {
        try {
            const response = await axios.get("http://localhost/wordpress/wp-json/adrentals/v1/user-details");
            if (response.data.logged_in) {
                setCustomer({
                    first_name: response.data.first_name,
                    last_name: response.data.last_name,
                    email: response.data.email,
                    phone: response.data.phone
                });
            }
        } catch (error) {
            console.error("Failed to fetch user details:", error);
        }
    };

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setCustomer((prev) => ({ ...prev, [name]: value }));
    };

    const processPayment = async () => {
        setProcessing(true);
        setError(null);

        try {
            if (!customer.first_name || !customer.email || !customer.phone) {
                throw new Error("Please enter all customer details.");
            }

            let orderData = {
                payment_method: selectedMethod,
                total: totalOrderCost,
                customer_details: customer
            };

            if (campaignType === "billboard") {
                orderData = {
                    ...orderData,
                    billboard_id: billboard.selectedBillboard.id,
                };
            } else if (campaignType === "radio") {
                orderData = {
                    ...orderData,
                    radio_station_id: radio.selectedStation.id,
                    radio_details: {
                        selectedDuration: radio.selectedDuration,
                        selectedTimeSlot: radio.selectedTimeSlot,
                        jingles: radio.jingles,
                        announcements: radio.announcements,
                        selectedSession: radio.selectedSession,
                        selectedSpots: radio.selectedSpots,
                        audioFile: radio.audioFile,
                        announcement: radio.announcement,
                        jingleCreationType: radio.jingleCreationType,
                        jingleText: radio.jingleText,
                        startDate: radio.startDate,
                        numberOfDays: radio.numberOfDays
                    }
                };
            }


            const response = await axios.post("http://localhost/wordpress/wp-json/adrentals/v1/create-checkout-session", orderData);

            if (response.data.checkout_url) {
                localStorage.setItem("campaign_data", JSON.stringify({
                    ...(campaignType === 'billboard' ? { billboard_id: billboard.selectedBillboard.id } : {}),
                    ...(campaignType === 'radio' ? { radio_station_id: radio.selectedStation.id } : {}),
                    payment_method: selectedMethod,
                    order_id: response.data.order_id,
                    campaign_type: campaignType, // Save campaign type
                    customer_details: customer, // Save customer details
                    total_order_cost: totalOrderCost, // Save total order cost
                    ...(campaignType === 'radio' ? { radio_details: orderData.radio_details } : {}), // Save radio details if campaign type is radio

                }));
                window.location.href = response.data.checkout_url;
            } else {
                throw new Error("No checkout URL received.");
            }
        } catch (error) {
            setError(error.message || "Failed to process payment.");
            setProcessing(false);
        }
    };

    if (loading) {
        return (
            <div className="space-y-6">
                <StepHeader title="Payment" onBack={() => setCurrentStep(4)} />
                <div className="flex justify-center items-center h-64">
                    <Loader2 className="h-8 w-8 animate-spin" />
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <StepHeader title="Payment" onBack={() => setCurrentStep(currentStep - 1)} />

            {error && (
                <Alert variant="destructive">
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}

            <Card>
                <CardContent className="p-6 space-y-6">
                    <div>
                        <h3 className="text-lg font-semibold mb-4">Customer Details</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label>First Name</Label>
                                <Input name="first_name" value={customer.first_name} onChange={handleInputChange} required />
                            </div>
                            <div>
                                <Label>Last Name</Label>
                                <Input name="last_name" value={customer.last_name} onChange={handleInputChange} />
                            </div>
                            <div>
                                <Label>Email</Label>
                                <Input type="email" name="email" value={customer.email} onChange={handleInputChange} required />
                            </div>
                            <div>
                                <Label>Phone</Label>
                                <Input type="tel" name="phone" value={customer.phone} onChange={handleInputChange} required />
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 className="text-lg font-semibold mb-4">Select Payment Method</h3>
                        <RadioGroup value={selectedMethod} onValueChange={setSelectedMethod} className="space-y-4">
                            {paymentMethods.map((method) => (
                                <div key={method.id} className="flex items-center space-x-3">
                                    <RadioGroupItem value={method.id} id={method.id} />
                                    <Label htmlFor={method.id} className="flex items-center gap-2">
                                        {method.icon && <img src={method.icon} alt={method.title} className="h-8 w-auto" />}
                                        <span>{method.title}</span>
                                    </Label>
                                </div>
                            ))}
                        </RadioGroup>
                    </div>

                    <div className="border-t pt-6">
                        <h3 className="text-lg font-semibold mb-4">Order Summary</h3>
                        <div className="space-y-2">
                            {campaignType === "billboard" && (
                                <>
                                    <div className="flex justify-between">
                                        <span>Billboard</span>
                                        <span>{billboard.selectedBillboard?.name}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Duration</span>
                                        <span>

                                            {billboard.selectedDuration.includes("daily") && billboard.numDays ? `${billboard.numDays} Days` : ""}
                                            {billboard.selectedDuration.includes("weekly") && billboard.numWeeks ? `${billboard.numWeeks} Weeks` : ""}
                                            {billboard.selectedDuration.includes("monthly") && billboard.numMonths ? `${billboard.numMonths} Months` : ""}

                                        </span>
                                    </div>

                                </>
                            )}
                            {campaignType === "radio" && (
                                <>
                                    <div className="flex justify-between">
                                        <span>Radio Station</span>
                                        <span>{radio.selectedStation?.title}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Duration</span>
                                        <span>{radio.selectedDuration}</span>
                                    </div>
                                    {radio.selectedTimeSlot && (
                                        <div className="flex justify-between">
                                            <span>Time Slot</span>
                                            <span>{radio.selectedTimeSlot}</span>
                                        </div>
                                    )}
                                    {radio.selectedSession && (
                                        <div className="flex justify-between">
                                            <span>Session</span>
                                            <span>{radio.selectedSession}</span>
                                        </div>
                                    )}
                                    {radio.selectedSpots && (
                                        <div className="flex justify-between">
                                            <span>Spots</span>
                                            <span>{radio.selectedSpots}</span>
                                        </div>
                                    )}
                                    {radio.announcement && (
                                        <div className="flex justify-between">
                                            <span>Announcement Text</span>
                                            <span className="truncate max-w-[150px]">{radio.announcement}</span>
                                        </div>
                                    )}
                                    {radio.jingleText && (
                                        <div className="flex justify-between">
                                            <span>Jingle Text</span>
                                            <span className="truncate max-w-[150px]">{radio.jingleText}</span>
                                        </div>
                                    )}
                                </>
                            )}
                            <div className="flex justify-between font-semibold">
                                <span>Total</span>
                                <span>${totalOrderCost}</span>
                            </div>
                        </div>
                    </div>

                    <Button className="w-full" onClick={processPayment} disabled={processing || !selectedMethod}>
                        {processing ? (
                            <>
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                Processing...
                            </>
                        ) : (
                            `Proceed to Payment`
                        )}
                    </Button>
                </CardContent>
            </Card>
        </div>
    );
};

export default PaymentStep;