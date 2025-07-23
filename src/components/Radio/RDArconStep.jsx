import React, { useRef } from 'react';
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardFooter } from "@/components/ui/card";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import useCampaignStore from "@/stores/useCampaignStore";
import StepHeader from '../StepHeader';

const CampaignPreview = ({ radio, arcon, totalCost }) => (
    <Card className="bg-gray-50 h-full">
        <CardContent className="p-4">
            <h3 className="font-semibold mb-4">Campaign Preview</h3>
            <div className="space-y-3">
                {/* <div>
                    <p className="text-sm text-gray-500">Selected Station</p>
                    <p className="text-sm font-medium">{radio.selectedStation?.name || '-'}</p>
                </div>
                <div>
                    <p className="text-sm text-gray-500">Time Slot</p>
                    <p className="text-sm font-medium">{radio.selectedTimeSlot?.name || '-'}</p>
                </div> */}
                <div>
                    <p className="text-sm text-gray-500">ARCON Status</p>
                    <p className="text-sm font-medium">
                        {arcon?.status === 'hasPermit' && 'Has ARCON Permit'}
                        {arcon?.status === 'needPermit' && 'Needs ARCON Permit'}
                        {arcon?.status === 'notRequired' && 'ARCON Not Required'}
                    </p>
                </div>
                {arcon?.selectedPermit && (
                    <div>
                        <p className="text-sm text-gray-500">ARCON License</p>
                        <p className="text-sm font-medium">{arcon.selectedPermit.name}</p>
                    </div>
                )}
                {arcon?.permitFile && (
                    <div>
                        <p className="text-sm text-gray-500">ARCON Permit</p>
                        <p className="text-sm font-medium">{arcon.permitFile.name}</p>
                    </div>
                )}
                <div>
                    <p className="text-sm text-gray-500">ARCON Cost</p>
                    <p className="text-sm font-medium">{adbridgeData.currency} {(arcon?.cost || 0)}</p>
                </div>
                <div className="pt-4 border-t">
                    <p className="text-sm text-gray-500">Total Cost</p>
                    <p className="text-lg font-semibold">
                        {adbridgeData.currency} {totalCost.toLocaleString()}
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
);

const RDArconStep = () => {
    const fileInputRef = useRef(null);
    const {
        radio,
        arcon = {}, // Default to empty object to avoid undefined errors
        campaignType,
        billboard,
        setArconDetails,
        setCurrentStep,
        currentStep,
        totalOrderCost,
    } = useCampaignStore();

    const handleArconStatusChange = (value) => {
        setArconDetails({
            status: value,
            selectedPermit: value === "needPermit" ? null : undefined,
            permitFile: null,
            cost: 0,
        });
    };

    const handleFileChange = (event) => {
        const file = event.target.files[0];
        if (file?.type === 'application/pdf') {
            setArconDetails({ permitFile: file });
        } else {
            alert('Please upload a PDF file');
            event.target.value = '';
        }
    };
    let arconTerms; // Declare outside the block

    if (campaignType !== 'billboard') {
        arconTerms = radio.selectedStation?.acron || [];
    } else {
        arconTerms = billboard.selectedBillboard?.acron || [];
    }

    const isNextDisabled = () => {
        if (arcon.status === "needPermit" && !arcon.selectedPermit) return true;
        if (arcon.status === "hasPermit" && !arcon.permitFile) return true;
        return !arcon.status;
    };

    return (
        <div className="container mx-auto px-4">
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2">
                    <StepHeader
                        title="Campaign Details"
                        onBack={() => setCurrentStep(3)}
                    />
                    <Card className="bg-white shadow-sm">
                        <CardContent className="p-4">
                            <RadioGroup
                                value={arcon.status || ""}
                                onValueChange={handleArconStatusChange}
                                className="space-y-2"
                            >
                                <div className="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded-lg">
                                    <RadioGroupItem value="hasPermit" id="hasPermit" />
                                    <Label htmlFor="hasPermit" className="text-sm font-medium cursor-pointer">
                                        I have ARCON Permit
                                    </Label>
                                </div>

                                <div className="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded-lg">
                                    <RadioGroupItem value="needPermit" id="needPermit" />
                                    <Label htmlFor="needPermit" className="text-sm font-medium cursor-pointer">
                                        I do not have ARCON Permit
                                    </Label>
                                </div>

                                <div className="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded-lg">
                                    <RadioGroupItem value="notRequired" id="notRequired" />
                                    <Label htmlFor="notRequired" className="text-sm font-medium cursor-pointer">
                                        My campaign does not require ARCON
                                    </Label>
                                </div>
                            </RadioGroup>

                            {arcon.status === "hasPermit" && (
                                <div className="mt-4">
                                    <Alert className="bg-blue-50 border-blue-200 mb-3">
                                        <AlertDescription className="text-blue-800 font-medium">
                                            Please upload your ARCON permit (PDF only)
                                        </AlertDescription>
                                    </Alert>
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept=".pdf"
                                        onChange={handleFileChange}
                                        className="block w-full text-sm text-gray-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-md file:border-0
                    file:text-sm file:font-semibold
                    file:bg-blue-50 file:text-blue-700
                    hover:file:bg-blue-100"
                                    />
                                </div>
                            )}

                            {arcon.status === "needPermit" && arconTerms.length > 0 && (
                                <div className="mt-4">
                                    <Alert className="bg-blue-50 border-blue-200 mb-3">
                                        <AlertDescription className="text-blue-800 font-medium">
                                            ARCON Terms
                                        </AlertDescription>
                                    </Alert>

                                    <RadioGroup
                                        value={arcon.selectedPermit?.name || ""}
                                        onValueChange={(value) => {
                                            const permit = arconTerms.find((t) => t.name === value);
                                            setArconDetails({
                                                selectedPermit: permit,
                                                cost: permit ? Number(permit.cost) : 0,
                                            });
                                        }}
                                        className="space-y-2"
                                    >
                                        {arconTerms.map((arconItem, index) => (
                                            <div
                                                key={index}
                                                className="flex items-start space-x-3 p-2 hover:bg-gray-50 rounded-lg"
                                            >
                                                <RadioGroupItem
                                                    value={arconItem.name}
                                                    id={`arcon-${index}`}
                                                    className="mt-1"
                                                />
                                                <Label
                                                    htmlFor={`arcon-${index}`}
                                                    className="text-sm font-medium cursor-pointer"
                                                >
                                                    I agree for {adbridgeData.website_title} to process {arconItem.name} ARCON license for me
                                                    <span className="block text-gray-600 mt-1">
                                                        (cost: {adbridgeData.currency} {parseInt(arconItem.cost).toLocaleString()})
                                                    </span>
                                                </Label>
                                            </div>
                                        ))}
                                    </RadioGroup>
                                </div>
                            )}
                        </CardContent>
                        <CardFooter className="flex justify-end gap-4 px-4 py-3 border-t">
                            <Button
                                variant="outline"
                                onClick={() => setCurrentStep(3)}
                                className="px-6"
                            >
                                Back
                            </Button>
                            <Button
                                onClick={() => setCurrentStep(currentStep + 1)}
                                disabled={isNextDisabled()}
                                className="px-6 bg-blue-600 hover:bg-blue-700 text-white"
                            >
                                Next
                            </Button>
                        </CardFooter>
                    </Card>
                </div>
                <div className="lg:col-span-1">
                    <CampaignPreview radio={radio} arcon={arcon} totalCost={totalOrderCost} />
                </div>
            </div>
        </div>
    );
};

export default RDArconStep;