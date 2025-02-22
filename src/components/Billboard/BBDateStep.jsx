import { useState, useEffect } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Video, Image, Youtube, Calendar, DollarSign, Clock } from "lucide-react";
import useCampaignStore from "@/stores/useCampaignStore";

const BBDateStep = () => {
    const { billboard, setCurrentStep, billboardFilters, setBillboardFilters } = useCampaignStore();
    const [startDate, setStartDate] = useState("");
    const [numDays, setNumDays] = useState(1);
    const [numWeeks, setNumWeeks] = useState(1);
    const [numMonths, setNumMonths] = useState(1);
    const [youtubeUrl, setYoutubeUrl] = useState("");
    const [mediaPreview, setMediaPreview] = useState(null);
    const [mediaType, setMediaType] = useState("image-video");
    const [endDatePreview, setEndDatePreview] = useState("");

    useEffect(() => {
        if (startDate) {
            const end = new Date(startDate);
            if (billboard.selectedDuration?.includes("daily")) {
                end.setDate(end.getDate() + numDays);
            } else if (billboard.selectedDuration?.includes("weekly")) {
                end.setDate(end.getDate() + numWeeks * 7);
            } else if (billboard.selectedDuration?.includes("monthly")) {
                end.setMonth(end.getMonth() + numMonths);
            }
            setEndDatePreview(end.toISOString().split("T")[0]);
        }
    }, [startDate, numDays, numWeeks, numMonths, billboard.selectedDuration]);

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setMediaPreview(URL.createObjectURL(file));
            setYoutubeUrl("");
        }
    };

    const handleYoutubeChange = (e) => {
        setYoutubeUrl(e.target.value);
        const videoId = e.target.value.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/)?.[1];
        if (videoId) {
            setMediaPreview(`https://img.youtube.com/vi/${videoId}/maxresdefault.jpg`);
        } else {
            setMediaPreview(null);
        }
    };

    const calculatePricing = () => {
        let basePrice = billboard.selectedBillboard?.pricing[billboard.selectedDuration] || 0;
        if (billboard.selectedDuration?.includes("daily")) {
            return basePrice * numDays;
        } else if (billboard.selectedDuration?.includes("weekly")) {
            return basePrice * numWeeks;
        } else if (billboard.selectedDuration?.includes("monthly")) {
            return basePrice * numMonths;
        }
        return basePrice;
    };

    const handleNext = () => {
        setBillboardFilters({
            ...billboardFilters,
            startDate,
            endDate: endDatePreview,
            totalPrice: calculatePricing(),
            mediaType,
            mediaUrl: mediaType === "youtube" ? youtubeUrl : mediaPreview
        });
        setCurrentStep(5);
    };

    const isNextDisabled = !startDate || (!mediaPreview && !youtubeUrl);

    return (
        <div className="flex flex-col lg:flex-row min-h-screen">
            {/* Left Column - Campaign Details Form */}
            <div className="w-full lg:w-2/3 p-4 lg:p-6 lg:border-r">
                <div className="max-w-2xl mx-auto">
                    <div className="flex items-center space-x-4 mb-6">
                        <Button variant="outline" onClick={() => setCurrentStep(3)} className="text-sm lg:text-base">
                            <span className="mr-2">←</span> Back
                        </Button>
                        <h2 className="text-xl lg:text-2xl font-semibold">Campaign Details</h2>
                    </div>

                    <Card className="mb-6">
                        <CardContent className="space-y-6 pt-6">
                            {/* Date Selection */}
                            <div className="space-y-4">
                                <Label className="flex items-center gap-2">
                                    <Calendar className="w-4 h-4" />
                                    Start Date
                                </Label>
                                <Input
                                    type="date"
                                    value={startDate}
                                    onChange={(e) => setStartDate(e.target.value)}
                                    min={new Date().toISOString().split("T")[0]}
                                    className="w-full"
                                />
                            </div>

                            {/* Duration Selections */}
                            {billboard.selectedDuration?.includes("daily") && (
                                <div className="space-y-4">
                                    <Label className="flex items-center gap-2">
                                        <Clock className="w-4 h-4" />
                                        Number of Days
                                    </Label>
                                    <Input
                                        type="number"
                                        min="1"
                                        max="30"
                                        value={numDays}
                                        onChange={(e) => setNumDays(Number(e.target.value))}
                                        className="w-full"
                                    />
                                </div>
                            )}

                            {billboard.selectedDuration?.includes("weekly") && (
                                <div className="space-y-4">
                                    <Label className="flex items-center gap-2">
                                        <Clock className="w-4 h-4" />
                                        Number of Weeks
                                    </Label>
                                    <Select value={String(numWeeks)} onValueChange={(value) => setNumWeeks(Number(value))}>
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Select weeks" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {[1, 2, 3, 4].map((week) => (
                                                <SelectItem key={week} value={String(week)}>
                                                    {week} {week > 1 ? "Weeks" : "Week"}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}

                            {billboard.selectedDuration?.includes("monthly") && (
                                <div className="space-y-4">
                                    <Label className="flex items-center gap-2">
                                        <Clock className="w-4 h-4" />
                                        Number of Months
                                    </Label>
                                    <Input
                                        type="number"
                                        min="1"
                                        max="12"
                                        value={numMonths}
                                        onChange={(e) => setNumMonths(Number(e.target.value))}
                                        className="w-full"
                                    />
                                </div>
                            )}

                            {/* Media Type Selection */}
                            <div className="space-y-4">
                                <Label>Choose Media Type</Label>
                                <RadioGroup value={mediaType} onValueChange={setMediaType} className="flex flex-col space-y-2">
                                    <div className="flex items-center space-x-2">
                                        <RadioGroupItem value="image-video" id="image-video" />
                                        <Label htmlFor="image-video" className="flex items-center space-x-2 cursor-pointer">
                                            <Image className="w-4 h-4" />
                                            <span>Image/Video</span>
                                        </Label>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <RadioGroupItem value="youtube" id="youtube" />
                                        <Label htmlFor="youtube" className="flex items-center space-x-2 cursor-pointer">
                                            <Youtube className="w-4 h-4" />
                                            <span>YouTube Video</span>
                                        </Label>
                                    </div>
                                </RadioGroup>
                            </div>

                            {/* Media Upload/URL Input */}
                            {mediaType === "image-video" && (
                                <div className="space-y-4">
                                    <Label>Upload Image/Video</Label>
                                    <Input
                                        type="file"
                                        accept="image/*,video/*"
                                        onChange={handleFileChange}
                                        className="cursor-pointer w-full"
                                    />
                                </div>
                            )}

                            {mediaType === "youtube" && (
                                <div className="space-y-4">
                                    <Label>YouTube Video URL</Label>
                                    <Input
                                        type="text"
                                        placeholder="Enter YouTube URL"
                                        value={youtubeUrl}
                                        onChange={handleYoutubeChange}
                                        className="w-full"
                                    />
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Mobile Order Summary */}
                    <div className="lg:hidden mb-6">
                        <OrderSummary
                            startDate={startDate}
                            endDatePreview={endDatePreview}
                            numDays={numDays}
                            numWeeks={numWeeks}
                            numMonths={numMonths}
                            billboard={billboard}
                            calculatePricing={calculatePricing}
                            mediaPreview={mediaPreview}
                        />
                    </div>
                </div>
            </div>

            {/* Right Column - Order Summary (Desktop) */}
            <div className="hidden lg:block w-1/3 p-6 bg-gray-50">
                <div className="sticky top-6">
                    <OrderSummary
                        startDate={startDate}
                        endDatePreview={endDatePreview}
                        numDays={numDays}
                        numWeeks={numWeeks}
                        numMonths={numMonths}
                        billboard={billboard}
                        calculatePricing={calculatePricing}
                        mediaPreview={mediaPreview}
                    />

                    {/* Checkout Button */}
                    <Button
                        className="w-full"
                        onClick={handleNext}
                        disabled={isNextDisabled}
                    >
                        Checkout Now
                    </Button>
                </div>
            </div>

            {/* Fixed Bottom Checkout Button (Mobile) */}
            <div className="lg:hidden fixed bottom-0 left-0 right-0 p-4 bg-white border-t">
                <Button
                    className="w-full"
                    onClick={handleNext}
                    disabled={isNextDisabled}
                >
                    Checkout Now
                </Button>
            </div>
        </div>
    );
};

// Extracted Order Summary Component
const OrderSummary = ({
    startDate,
    endDatePreview,
    numDays,
    numWeeks,
    numMonths,
    billboard,
    calculatePricing,
    mediaPreview
}) => (
    <>
        <h3 className="text-lg lg:text-xl font-semibold mb-6">Order Summary</h3>

        <Card className="mb-4">
            <CardContent className="pt-6">
                <div className="space-y-2">
                    <div className="grid grid-cols-2 gap-4">
                        <span className="text-gray-600">Starting Date:</span>
                        <span className="font-medium">{startDate}</span>

                        <span className="text-gray-600">Ending Date:</span>
                        <span className="font-medium">{endDatePreview}</span>

                        <span className="text-gray-600">Duration:</span>
                        <span className="font-medium">
                            {billboard.selectedDuration?.includes("daily") && `${numDays} day${numDays > 1 ? 's' : ''}`}
                            {billboard.selectedDuration?.includes("weekly") && `${numWeeks} week${numWeeks > 1 ? 's' : ''}`}
                            {billboard.selectedDuration?.includes("monthly") && `${numMonths} month${numMonths > 1 ? 's' : ''}`}
                        </span>
                    </div>

                    <div className="pt-4 border-t grid grid-cols-2 gap-4">
                        <span className="text-gray-600">Total Cost:</span>
                        <span className="text-lg lg:text-xl font-bold">${calculatePricing().toLocaleString()}</span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card className="mb-6">
            <CardHeader>
                <CardTitle className="text-base lg:text-lg">Media Preview</CardTitle>
            </CardHeader>
            <CardContent>
                {mediaPreview ? (
                    <div className="relative aspect-video w-full overflow-hidden rounded-lg bg-gray-100">
                        <img
                            src={mediaPreview}
                            alt="Media preview"
                            className="w-full h-full object-cover"
                        />
                    </div>
                ) : (
                    <div className="aspect-video w-full rounded-lg bg-gray-100 flex items-center justify-center">
                        <p className="text-gray-500">No media selected</p>
                    </div>
                )}
            </CardContent>
        </Card>
    </>
);

export default BBDateStep;