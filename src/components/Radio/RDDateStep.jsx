import { useEffect, useState, useCallback, useMemo, memo } from "react";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Upload, ChevronLeft, Video } from "lucide-react";
import useCampaignStore from "@/stores/useCampaignStore";
import axios from "axios";
import { useStore } from 'zustand';
import StepHeader from "@/components/StepHeader";

// Memoized components
const ScriptTypeSelector = memo(({ scriptType, setScriptType }) => (
    <Card>
        <CardHeader>
            <CardTitle>Script Type</CardTitle>
        </CardHeader>
        <CardContent>
            <div className="flex space-x-4">
                <Button
                    variant={scriptType === "jingles" ? "default" : "outline"}
                    onClick={() => setScriptType("jingles")}
                    className="w-full"
                >
                    Jingle
                </Button>
                <Button
                    variant={scriptType === "announcements" ? "default" : "outline"}
                    onClick={() => setScriptType("announcements")}
                    className="w-full"
                >
                    Announcement
                </Button>
            </div>
        </CardContent>
    </Card>
));

const MediaUploader = memo(({ handleMediaUpload, mediaPreview, mediaType }) => (
    <div className="border-2 border-dashed rounded-lg p-6 text-center">
        <Input
            type="file"
            accept={mediaType === "audio" ? "audio/*" : "video/*"}
            className="hidden"
            id="media-upload"
            onChange={handleMediaUpload}
        />
        <label htmlFor="media-upload" className="cursor-pointer">
            {mediaType === "audio" ? (
                <Upload className="w-12 h-12 mx-auto mb-4 text-gray-400" />
            ) : (
                <Video className="w-12 h-12 mx-auto mb-4 text-gray-400" />
            )}
            <p className="text-sm text-gray-600">Click to upload or drag and drop</p>
            <p className="text-xs text-gray-500 mt-1">
                {mediaType === "audio" ? "MP3 or WAV" : "MP4 or MOV"} (max. 5MB)
            </p>
        </label>
        {mediaPreview && (
            mediaType === "audio" ? (
                <audio controls className="mt-4 w-full">
                    <source src={mediaPreview} type="audio/mpeg" />
                </audio>
            ) : (
                <video controls className="mt-4 w-full max-h-48">
                    <source src={mediaPreview} type="video/mp4" />
                </video>
            )
        )}
    </div>
));

const SessionSelector = memo(({
    scriptType,
    sessions,
    selectedSession,
    setSelectedSession,
    selectedSpots,
    setSelectedSpots
}) => {
    const maxSlots = useMemo(() =>
        sessions[scriptType]?.find(s => s.name === selectedSession)?.max_slots || 0
        , [sessions, scriptType, selectedSession]);

    return (
        <Card>
            <CardHeader>
                <CardTitle>Session Details</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <Select
                    value={selectedSession}
                    onValueChange={setSelectedSession}
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Select Session" />
                    </SelectTrigger>
                    <SelectContent>
                        {(sessions[scriptType] || []).map(session => (
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
                            {Array.from({ length: parseInt(maxSlots) }, (_, i) => i + 1).map(num => (
                                <SelectItem key={num} value={num.toString()}>
                                    {num} spot{num > 1 ? 's' : ''}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
            </CardContent>
        </Card>
    );
});

const DurationPicker = memo(({
    startDate,
    setStartDate,
    numberOfDays,
    setNumberOfDays
}) => (
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
                        min="1"
                    />
                    <p className="text-sm text-gray-500 mt-1">Number of Days</p>
                </div>
            </div>
        </CardContent>
    </Card>
));

const RDDateStep = () => {
    const { radio, setRadioFilters, setCurrentStep, setRadioTotalCost, campaignType } = useCampaignStore();
    //const radio = useStore(useCampaignStore, (state) => state.radio);

    // State initialization
    const [scriptType, setScriptType] = useState(() => radio.scriptType || null);

    const [sessions, setSessions] = useState({ jingles: [], announcements: [] });
    const [selectedSession, setSelectedSession] = useState(() => radio.selectedSession || "");
    const [selectedSpots, setSelectedSpots] = useState(() => radio.selectedSpots || "");
    const [mediaFile, setMediaFile] = useState(radio.mediaFile || null); // Add mediaFile to state
    const [mediaPreview, setMediaPreview] = useState(null);
    const [announcement, setAnnouncement] = useState(() => radio.announcement || "");
    const [jingleCreationType, setJingleCreationType] = useState(() => radio.jingleCreationType || "upload");
    const [jingleText, setJingleText] = useState(() => radio.jingleText || "");
    const [startDate, setStartDate] = useState(() => radio.startDate || "");
    const [numberOfDays, setNumberOfDays] = useState(() => radio.numberOfDays || "");

    const jingleCreationCost = adbridgeData.jingle_creation_cost;
    const mediaType = campaignType === "tv" ? "video" : "audio";

    // Session data fetching
    const fetchSessions = useCallback(async () => {
        if (!radio.selectedStation?.id) return;
        try {
            const response = await axios.get(
                `${adbridgeData.restUrl}adrentals/v1/campaign/${radio.selectedStation.id}`
            );
            setSessions(response.data);
        } catch (error) {
            console.error("Error fetching campaign sessions:", error);
        }
    }, [radio.selectedStation?.id]);

    useEffect(() => {
        fetchSessions();
    }, [fetchSessions]);

    // Media handling
    const handleMediaUpload = useCallback((e) => {
        const file = e.target.files[0];
        if (file) {
            setMediaFile(file); // Store the file object
            setMediaPreview(URL.createObjectURL(file));
        }
    }, []);

    // Load announcement message from store when component mounts
    useEffect(() => {
        if (radio.announcement) {
            setAnnouncement(radio.announcement);
        }
    }, [radio.announcement]);

    // Set script type based on prior selection or default
    useEffect(() => {
        if (radio.scriptType) {
            setScriptType(radio.scriptType);
        }
    }, [radio.scriptType]);

    // Cost calculation
    const totalCost = useMemo(() => {
        if (!selectedSession || !selectedSpots || !numberOfDays) return 0;
        const session = sessions[scriptType]?.find(s => s.name === selectedSession);
        const baseCost = session ? session.price_per_slot * selectedSpots * numberOfDays : 0;
        const additionalCost = scriptType === "jingles" && jingleCreationType === "create" ? jingleCreationCost : 0;
        return baseCost + additionalCost;
    }, [scriptType, selectedSession, selectedSpots, numberOfDays, jingleCreationType, sessions]);

    useEffect(() => {
        setRadioTotalCost(totalCost);
    }, [totalCost, setRadioTotalCost]);

    // Form submission
    const handleNext = useCallback(() => {
        const calculatedEndDate = new Date(startDate);
        calculatedEndDate.setDate(calculatedEndDate.getDate() + parseInt(numberOfDays) - 1);

        setRadioFilters({
            scriptType,
            selectedSession,
            selectedSpots,
            mediaFile,
            announcement,
            jingleText: scriptType === "jingles" && jingleCreationType === "create" ? jingleText : null,
            startDate,
            numberOfDays,
            endDate: calculatedEndDate.toISOString().slice(0, 10),
            jingleCreationType,
        });
        setCurrentStep(4);
    }, [scriptType, selectedSession, selectedSpots, mediaFile, announcement, jingleText, startDate, numberOfDays, jingleCreationType, setRadioFilters, setCurrentStep]);

    const handleBack = useCallback(() => {
        // Save state before going back
        setRadioFilters({
            scriptType,
            selectedSession,
            selectedSpots,
            mediaFile,
            announcement,
            jingleText,
            startDate,
            numberOfDays,
            jingleCreationType,
        });
        setCurrentStep(2);
    }, [scriptType, selectedSession, selectedSpots, mediaFile, announcement, jingleText, startDate, numberOfDays, jingleCreationType, setRadioFilters, setCurrentStep]);

    // Form validation
    const isFormValid = useMemo(() => {
        const basicValid = scriptType && selectedSession && selectedSpots && startDate && numberOfDays;
        const jingleValid = scriptType === "jingles" && (
            (jingleCreationType === "upload" && mediaFile) ||
            (jingleCreationType === "create" && jingleText)
        );
        const announcementValid = scriptType === "announcements" && announcement;

        return basicValid && (jingleValid || announcementValid);
    }, [scriptType, selectedSession, selectedSpots, startDate, numberOfDays, jingleCreationType, mediaFile, jingleText, announcement]);

    return (
        <div className="max-w-5xl mx-auto p-6 space-y-6">

            <StepHeader
                title="Create Campaign"
                onBack={handleBack}
            />

            {/*     <div className="flex items-center justify-between mb-8">
                <div className="space-y-1">
                    <h2 className="text-3xl font-bold">Create Campaign</h2>
                    <p className="text-gray-500">Configure campaign details</p>
                </div>
                <Button variant="outline" className="flex items-center gap-2" onClick={handleBack}>
                    <ChevronLeft className="h-4 w-4" />
                    Back
                </Button>
            </div> */}

            <ScriptTypeSelector scriptType={scriptType} setScriptType={setScriptType} />

            {scriptType === "jingles" && (
                <Card>
                    <CardHeader>
                        <CardTitle>Jingle Details</CardTitle>
                    </CardHeader>
                    <CardContent>
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
                                Create Jingle (+{adbridgeData.currency} {jingleCreationCost})
                            </Button>
                        </div>
                        {jingleCreationType === "upload" ? (
                            <MediaUploader
                                handleMediaUpload={handleMediaUpload}
                                mediaPreview={mediaPreview}
                                mediaType={mediaType}
                            />
                        ) : (
                            <Textarea
                                value={jingleText}
                                onChange={(e) => setJingleText(e.target.value)}
                                placeholder="Enter your jingle text"
                                className="h-32"
                            />
                        )}
                    </CardContent>
                </Card>
            )}

            {scriptType === "announcements" && (
                <Card>
                    <CardHeader>
                        <CardTitle>Announcement Message</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Textarea
                            value={announcement}
                            onChange={(e) => setAnnouncement(e.target.value)}
                            placeholder="Enter your announcement message (max 100 words)"
                            className="h-32"
                        />
                    </CardContent>
                </Card>
            )}

            <SessionSelector
                scriptType={scriptType}
                sessions={sessions}
                selectedSession={selectedSession}
                setSelectedSession={setSelectedSession}
                selectedSpots={selectedSpots}
                setSelectedSpots={setSelectedSpots}
            />

            <DurationPicker
                startDate={startDate}
                setStartDate={setStartDate}
                numberOfDays={numberOfDays}
                setNumberOfDays={setNumberOfDays}
            />

            <div className="flex justify-end space-x-4">
                <Button
                    className="w-full"
                    onClick={handleNext}
                    disabled={!isFormValid}
                >
                    Next: Review Campaign
                </Button>
            </div>
        </div>
    );
};

export default RDDateStep;