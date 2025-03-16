import React from 'react';
import { useState, useEffect } from "react";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Loader2, ArrowLeft, ShoppingCart } from "lucide-react";
import axios from "axios";
import StepHeader from "./StepHeader";
import useCampaignStore from "@/stores/useCampaignStore";

const PaymentStep = () => {
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState(null);
    const [savedCart, setSavedCart] = useState(null);

    const {
        currentStep,
        campaignType,
        billboard,
        radio,
        arcon,
        totalOrderCost,
        setCurrentStep,
    } = useCampaignStore();

    // Check for existing abandoned cart on component mount
    useEffect(() => {
        /*         const checkExistingCart = async () => {
                    try {
                        const response = await axios.get(
                            "http://localhost/wordpress/wp-json/adrentals/v1/check-abandoned-cart"
                        );
                        if (response.data.success && response.data.cart) {
                            setSavedCart(response.data.cart);
                        }
                    } catch (error) {
                        console.error("Failed to check abandoned cart:", error);
                    }
                }; */

        //checkExistingCart();
    }, []);

    const createOrder = async () => {
        setProcessing(true);
        setError(null);

        try {
            // Create FormData object to handle file uploads
            const formData = new FormData();

            // Prepare campaign details based on type
            const campaignData = {
                campaign_type: campaignType,
                total_cost: totalOrderCost,
                campaign_details: campaignType === "billboard" ? {
                    billboard_id: billboard.selectedBillboard.id,
                    billboard_name: billboard.selectedBillboard.name,
                    duration_type: billboard.selectedDuration,
                    num_days: billboard.numDays || 0,
                    num_weeks: billboard.numWeeks || 0,
                    num_months: billboard.numMonths || 0,
                    location: billboard.selectedBillboard.location,
                    attributes: billboard.selectedBillboard.attributes,
                    start_date: billboard.startDate,
                    end_date: billboard.endDate,
                    media_type: billboard.mediaType,
                    media_url: billboard.mediaUrl,
                } : {
                    station_id: radio.selectedStation.id,
                    station_name: radio.selectedStation.title,
                    attributes: radio.selectedBillboard.attributes,
                    duration: radio.selectedDuration,
                    time_slot: radio.selectedTimeSlot,
                    session: radio.selectedSession,
                    spots: radio.selectedSpots,
                    start_date: radio.startDate,
                    end_date: radio.endDate,
                    number_of_days: radio.numberOfDays,
                    script_type: radio.scriptType,
                    announcement: radio.announcementText,
                    jingle_text: radio.jingleText,
                    jingle_creation_type: radio.jingleCreationType,
                    //announcements: radio.announcements,
                    //jingles: radio.jingles
                }
            };

            // Add campaign data to FormData
            formData.append('campaign_data', JSON.stringify(campaignData));

            // Add files based on campaign type
            if (campaignType === "billboard" && billboard.mediaFile) {
                formData.append('media_file', billboard.mediaFile);
            }

            if ((campaignType === "radio" || campaignType === "tv") && radio.mediaFile) {
                formData.append('media_file', radio.mediaFile);
            }


            // Add ARCON permit file if exists
            if (arcon.permitFile) {
                formData.append('arcon_permit', arcon.permitFile);
            }

            formData.append('action', 'create_campaign_order'); // Add action for WordPress AJAX
            formData.append('nonce', adbridgeData.nonce);
            // Save campaign data and get WooCommerce checkout URL
            const response = await axios.post(adbridgeData.ajaxUrl, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                },
            });
            if (response.data.success && response.data.checkout_url) {

                // Redirect to WooCommerce checkout
                window.location.href = response.data.checkout_url;
            } else {
                throw new Error("Failed to create order");
            }
        } catch (error) {
            setError(error.response?.data?.message || "Failed to process order.");
            setProcessing(false);
        }
    };

    const resumeExistingCart = () => {
        if (savedCart?.checkout_url) {
            window.location.href = savedCart.checkout_url;
        }
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    return (
        <div className="space-y-6">
            <StepHeader
                title="Review & Checkout"
                onBack={() => setCurrentStep(currentStep - 1)}
            />

            {error && (
                <Alert variant="destructive">
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}

            {savedCart && (
                <Alert>
                    <AlertDescription className="flex items-center justify-between">
                        <span>You have an existing campaign in your cart.</span>
                        <Button onClick={resumeExistingCart} variant="outline" size="sm">
                            <ShoppingCart className="mr-2 h-4 w-4" />
                            Resume Checkout
                        </Button>
                    </AlertDescription>
                </Alert>
            )}

            <Card>
                <CardContent className="p-6 space-y-6">
                    <div>
                        <h3 className="text-lg font-semibold mb-4">Campaign Summary</h3>
                        <div className="space-y-4">
                            {campaignType === "billboard" && (
                                <div className="grid grid-cols-2 gap-2">
                                    <span className="font-medium">Billboard:</span>
                                    <span>{billboard.selectedBillboard?.name}</span>

                                    <span className="font-medium">Location:</span>
                                    <span>{billboard.selectedBillboard?.location}</span>

                                    {billboard.selectedBillboard?.attributes.map((attr, index) => (
                                        <React.Fragment key={`attr-${index}`}>
                                            <span className="font-medium">{attr.attribute}:</span>
                                            <span>{attr.value}</span>
                                        </React.Fragment>
                                    ))}


                                    <span className="font-medium">Duration Type:</span>
                                    <span className="capitalize">
                                        {billboard.selectedDuration.replace('_', ' ')}
                                    </span>

                                    <span className="font-medium">Campaign Period:</span>
                                    <span>
                                        {formatDate(billboard.startDate)} - {formatDate(billboard.endDate)}
                                    </span>

                                    <span className="font-medium">Media Type:</span>
                                    <span className="capitalize">{billboard.mediaType}</span>

                                    {billboard.mediaUrl && (
                                        <>
                                            <span className="font-medium">Media URL:</span>
                                            <span className="truncate">{billboard.mediaUrl}</span>
                                        </>
                                    )}

                                    {billboard.mediaFile && (
                                        <>
                                            <span className="font-medium">Media File:</span>
                                            <span className="truncate">{billboard.mediaFile.name}</span>
                                        </>
                                    )}
                                </div>
                            )}

                            {(campaignType === "radio" || campaignType === "tv") && (
                                <div className="grid grid-cols-2 gap-2">
                                    <span className="font-medium">{campaignType === "radio" ? "Radio Station:" : "TV Channel:"}</span>
                                    <span>{radio.selectedStation?.title}</span>

                                    <span className="font-medium">No of Spots:</span>
                                    <span>{radio.selectedSpots}</span>

                                    {radio.selectedStation?.attributes.map((attr, index) => (
                                        <React.Fragment key={`attr-${index}`}>
                                            <span className="font-medium">{attr.attribute}:</span>
                                            <span>{attr.value}</span>
                                        </React.Fragment>
                                    ))}


                                    {radio.selectedTimeSlot && (
                                        <>
                                            <span className="font-medium">Time Slot:</span>
                                            <span>{radio.selectedTimeSlot}</span>
                                        </>
                                    )}

                                    {radio.selectedSession && (
                                        <>
                                            <span className="font-medium">{campaignType === "radio" ? "Session:" : "Air Time:"}</span>
                                            <span>{radio.selectedSession}</span>
                                        </>
                                    )}

                                    {radio.startDate && (
                                        <>
                                            <span className="font-medium">Start Date:</span>
                                            <span>{formatDate(radio.startDate)}</span>
                                        </>
                                    )}

                                    {radio.endDate && (
                                        <>
                                            <span className="font-medium">End Date:</span>
                                            <span>{formatDate(radio.endDate)}</span>
                                        </>
                                    )}

                                    {radio.numberOfDays && (
                                        <>
                                            <span className="font-medium">Number of Days:</span>
                                            <span>{radio.numberOfDays}</span>
                                        </>
                                    )}

                                    {radio.audioFile && (
                                        <>
                                            <span className="font-medium">Audio File:</span>
                                            <span className="truncate">{radio.audioFile.name}</span>
                                        </>
                                    )}
                                </div>
                            )}

                            {arcon.permitFile && (
                                <div className="grid grid-cols-2 gap-2 border-t pt-4">
                                    <span className="font-medium">ARCON Permit:</span>
                                    <span className="truncate">{arcon.permitFile.name}</span>

                                    <span className="font-medium">ARCON Status:</span>
                                    <span>{arcon.status}</span>

                                    <span className="font-medium">ARCON Cost:</span>
                                    <span>${arcon.cost.toFixed(2)}</span>
                                </div>
                            )}

                            <div className="border-t pt-4 mt-4">
                                <div className="flex justify-between items-center">
                                    <span className="text-lg font-semibold">Total Amount:</span>
                                    <span className="text-lg font-bold">{adbridgeData.currency} {totalOrderCost.toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="flex gap-4">
                        <Button
                            variant="outline"
                            onClick={() => setCurrentStep(currentStep - 1)}
                            disabled={processing}
                        >
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Back
                        </Button>
                        <Button
                            className="flex-1"
                            onClick={createOrder}
                            disabled={processing}
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Processing...
                                </>
                            ) : (
                                <>
                                    <ShoppingCart className="mr-2 h-4 w-4" />
                                    Proceed to Checkout
                                </>
                            )}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default PaymentStep;