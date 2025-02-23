import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Upload, ChevronLeft } from "lucide-react";
import useCampaignStore from "@/stores/useCampaignStore";
import axios from "axios";
import { useStore } from 'zustand';

const RDDateStep = () => {
    const { radio, setRadioFilters, setCurrentStep, setTotalOrderCost } = useCampaignStore();
    const currentRadioState = useStore(useCampaignStore, (state) => state.radio);

    // Base states
    const [campaignType, setCampaignType] = useState(currentRadioState.campaignType || null);
    const [sessions, setSessions] = useState({ jingles: [], announcements: [] });
    const [selectedSession, setSelectedSession] = useState(currentRadioState.selectedSession || "");
    const [selectedSpots, setSelectedSpots] = useState(currentRadioState.selectedSpots || "");
    const [audioFile, setAudioFile] = useState(currentRadioState.audioFile || null);
    const [audioPreview, setAudioPreview] = useState(null);
    const [announcement, setAnnouncement] = useState(currentRadioState.announcement || "");
    const [jingleCreationType, setJingleCreationType] = useState(currentRadioState.jingleCreationType || "upload");
    const [jingleText, setJingleText] = useState(currentRadioState.jingleText || "");
    const [startDate, setStartDate] = useState(currentRadioState.startDate || "");
    const [numberOfDays, setNumberOfDays] = useState(currentRadioState.numberOfDays || "");
    const [currentSection, setCurrentSection] = useState("details"); // New state for section management
    const jingleCreationCost = 50;

    // Existing useEffects and handlers...
    useEffect(() => {
        if (!radio.selectedStation) return;
        const fetchSessions = async () => {
            try {
                const response = await axios.get(`http://localhost/wordpress/wp-json/adrentals/v1/campaign/${radio.selectedStation.id}`);
                setSessions(response.data);
            } catch (error) {
                console.error("Error fetching campaign sessions:", error);
            }
        };
        fetchSessions();
    }, [radio.selectedStation]);

    useEffect(() => {
        if (audioFile) {
            const url = URL.createObjectURL(audioFile);
            setAudioPreview(url);
        } else {
            setAudioPreview(null);
        }
    }, [audioFile]);

    const handleAudioUpload = (e) => {
        const file = e.target.files[0];
        if (file) {
            setAudioFile(file);
        }
    };

    const calculateTotalCost = () => {
        if (!selectedSession || !selectedSpots || !numberOfDays) return 0;
        let baseCost = 0;
        const session = sessions[campaignType]?.find(s => s.name === selectedSession);
        if (session) {
            baseCost = session.price_per_slot * parseInt(selectedSpots) * parseInt(numberOfDays);
        }
        let additionalCost = 0;
        if (campaignType === "jingles" && jingleCreationType === "create") {
            additionalCost = jingleCreationCost;
        }
        return baseCost + additionalCost;
    };

    const handleBack = () => {
        if (currentSection === "summary") {
            setCurrentSection("details");
        } else {
            setCurrentStep(2);
        }
    };

    const handleNext = () => {
        if (currentSection === "details") {
            setCurrentSection("summary");
        } else {
            setRadioFilters({
                selectedCampaignType: campaignType,
                selectedSession,
                selectedSpots,
                audioFile: campaignType === "jingles" && jingleCreationType === "upload" ? audioFile : null,
                announcementText: campaignType === "announcements" ? announcement : null,
                jingleText: campaignType === "jingles" && jingleCreationType === "create" ? jingleText : null,
                startDate,
                numberOfDays,
                jingleCreationType: campaignType === "jingles" ? jingleCreationType : "upload",
            });
            setCurrentStep(4);
        }
    };

    const CampaignDetails = () => (
        <div className="space-y-6">
            {/* Campaign Type Card */}
            <Card>
                <CardHeader>
                    <CardTitle>Campaign Type</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="flex space-x-4">
                        <Button
                            variant={campaignType === "jingles" ? "default" : "outline"}
                            onClick={() => setCampaignType("jingles")}
                            className="w-full"
                        >
                            Jingle
                        </Button>
                        <Button
                            variant={campaignType === "announcements" ? "default" : "outline"}
                            onClick={() => setCampaignType("announcements")}
                            className="w-full"
                        >
                            Announcement
                        </Button>
                    </div>
                </CardContent>
            </Card>

            {/* Campaign Content Card */}
            {campaignType && (
                <Card>
                    <CardHeader>
                        <CardTitle>{campaignType === "jingles" ? "Jingle Details" : "Announcement Message"}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {campaignType === "jingles" ? (
                            <>
                                <div className="flex space-x-4 mb-4">
                                    <Button
                                        variant={jingleCreationType === "upload" ? "default" : "outline"}
                                        onClick={() => setJingleCreationType("upload")}
                                        className="w-full"
                                    >
                                        Upload Jingle
                                    </Button>
                                    <Button
                                        variant={jingleCreationType === "create" ? "default" : "outline"}
                                        onClick={() => setJingleCreationType("create")}
                                        className="w-full"
                                    >
                                        Create Jingle (+${jingleCreationCost})
                                    </Button>
                                </div>

                                {jingleCreationType === "upload" ? (
                                    <div className="border-2 border-dashed rounded-lg p-6 text-center">
                                        <Input
                                            type="file"
                                            accept="audio/*"
                                            className="hidden"
                                            id="audio-upload"
                                            onChange={handleAudioUpload}
                                        />
                                        <label htmlFor="audio-upload" className="cursor-pointer">
                                            <Upload className="w-12 h-12 mx-auto mb-4 text-gray-400" />
                                            <p className="text-sm text-gray-600">Click to upload or drag and drop</p>
                                            <p className="text-xs text-gray-500 mt-1">MP3 or WAV (max. 5MB)</p>
                                        </label>
                                    </div>
                                ) : (
                                    <Textarea
                                        placeholder="Enter your jingle text"
                                        value={jingleText}
                                        onChange={(e) => setJingleText(e.target.value)}
                                        className="h-32"
                                    />
                                )}
                            </>
                        ) : (
                            <Textarea
                                placeholder="Enter your announcement message (max 100 words)"
                                value={announcement}
                                onChange={(e) => setAnnouncement(e.target.value)}
                                className="h-32"
                            />
                        )}
                    </CardContent>
                </Card>
            )}

            {/* Session Selection Card */}
            <Card>
                <CardHeader>
                    <CardTitle>Session Details</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <Select value={selectedSession} onValueChange={setSelectedSession}>
                        <SelectTrigger>
                            <SelectValue placeholder="Select Session" />
                        </SelectTrigger>
                        <SelectContent>
                            {(sessions[campaignType] || []).map(session => (
                                <SelectItem key={session.name} value={session.name}>
                                    {session.name} (${session.price_per_slot}/slot)
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    {selectedSession && (
                        <Select value={selectedSpots} onValueChange={setSelectedSpots}>
                            <SelectTrigger>
                                <SelectValue placeholder="Number of Spots" />
                            </SelectTrigger>
                            <SelectContent>
                                {Array.from({ length: parseInt(sessions[campaignType]?.find(s => s.name === selectedSession)?.max_slots || 0) }, (_, i) => i + 1).map(num => (
                                    <SelectItem key={num} value={num.toString()}>
                                        {num} spot{num > 1 ? 's' : ''}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                </CardContent>
            </Card>
        </div>
    );

    const CampaignSummary = () => (
        <div className="space-y-6">
            {/* Campaign Duration Card */}
            <Card>
                <CardHeader>
                    <CardTitle>Campaign Duration</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Input
                                type="date"
                                value={startDate}
                                onChange={(e) => setStartDate(e.target.value)}
                            />
                            <p className="text-sm text-gray-500 mt-1">Starting Date</p>
                        </div>
                        <div>
                            <Input
                                type="number"
                                value={numberOfDays}
                                onChange={(e) => setNumberOfDays(e.target.value)}
                            />
                            <p className="text-sm text-gray-500 mt-1">Number of Days</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Preview Card */}
            <Card>
                <CardHeader>
                    <CardTitle>Campaign Preview</CardTitle>
                </CardHeader>
                <CardContent>
                    {campaignType === "jingles" ? (
                        jingleCreationType === "upload" ? (
                            audioPreview && (
                                <audio controls className="w-full">
                                    <source src={audioPreview} type={audioFile.type} />
                                </audio>
                            )
                        ) : (
                            <div className="p-4 bg-gray-50 rounded-lg">
                                <p className="text-gray-700">{jingleText}</p>
                            </div>
                        )
                    ) : (
                        <div className="p-4 bg-gray-50 rounded-lg">
                            <p className="text-gray-700">{announcement}</p>
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Order Summary Card */}
            <Card>
                <CardHeader>
                    <CardTitle>Order Summary</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        <div className="flex justify-between">
                            <span>Campaign Type</span>
                            <span className="font-medium">
                                {campaignType === "jingles" ? "Jingle" : "Announcement"}
                            </span>
                        </div>
                        {selectedSession && (
                            <div className="flex justify-between">
                                <span>Session</span>
                                <span className="font-medium">{selectedSession}</span>
                            </div>
                        )}
                        {selectedSpots && (
                            <div className="flex justify-between">
                                <span>Spots</span>
                                <span className="font-medium">{selectedSpots}</span>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <span>Duration</span>
                            <span className="font-medium">{numberOfDays} days</span>
                        </div>
                        {campaignType === "jingles" && jingleCreationType === "create" && (
                            <div className="flex justify-between">
                                <span>Creation Fee</span>
                                <span className="font-medium">+${jingleCreationCost}</span>
                            </div>
                        )}
                        <div className="border-t pt-4 mt-4">
                            <div className="flex justify-between text-lg font-bold">
                                <span>Total Cost</span>
                                <span>${calculateTotalCost().toLocaleString()}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );

    return (
        <div className="max-w-5xl mx-auto p-6 space-y-6">
            <div className="flex items-center justify-between mb-8">
                <div className="space-y-1">
                    <h2 className="text-3xl font-bold">Create Campaign</h2>
                    <p className="text-gray-500">
                        {currentSection === "details" ? "Configure campaign details" : "Review and finalize"}
                    </p>
                </div>
                <Button
                    variant="outline"
                    className="flex items-center gap-2"
                    onClick={handleBack}
                >
                    <ChevronLeft className="h-4 w-4" />
                    Back
                </Button>
            </div>

            {currentSection === "details" ? <CampaignDetails /> : <CampaignSummary />}

            <div className="flex justify-end space-x-4">
                <Button
                    className="w-full"
                    onClick={handleNext}
                    disabled={!campaignType || !selectedSession || !selectedSpots ||
                        (currentSection === "summary" && (!startDate || !numberOfDays)) ||
                        (campaignType === "jingles" && jingleCreationType === "upload" && !audioFile) ||
                        (campaignType === "jingles" && jingleCreationType === "create" && !jingleText) ||
                        (campaignType === "announcements" && !announcement)}
                >
                    {currentSection === "details" ? "Next: Review Campaign" : "Finalize Campaign"}
                </Button>
            </div>
        </div>
    );
};

export default RDDateStep;